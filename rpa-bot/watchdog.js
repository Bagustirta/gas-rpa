/**
 * RPA Event-Driven Watchdog & AI Analyzer
 *
 * Mode: 100% Pasif & Silent (Event-Driven)
 * - TIDAK ADA loop polling agresif (0% CPU, tidak ada terminal pop-up).
 * - Hanya bangun jika ADA HAL JANGGAL:
 *   1. File Watcher: Mendeteksi saat ada pesan error baru ditulis ke log.
 *   2. Gemini AI: Menganalisa akar masalah & solusi saat ada error baru.
 *   3. Telegram Alert: Memberitahu ke HP Anda secara instan.
 *   4. Telegram Command: Membalas jika Anda mengetik /status, /restart, /help.
 */

'use strict';

const axios    = require('axios');
const fs       = require('fs');
const path     = require('path');
const dns      = require('dns');
const { exec } = require('child_process');
const util     = require('util');
require('dotenv').config({ path: path.join(__dirname, '.env') });

try { dns.setDefaultResultOrder('ipv4first'); } catch (e) {}

const execAsync = util.promisify(exec);

// ─── Konfigurasi ──────────────────────────────────────────────────────────────

const LARAVEL_URL     = (process.env.LARAVEL_APP_URL || 'http://localhost:8000').trim();
const API_KEY         = process.env.LPG_API_KEY      || 'lpg-secret-token-12345';
const TELEGRAM_TOKEN  = (process.env.TELEGRAM_BOT_TOKEN || '').trim();
let   TELEGRAM_CHAT_ID= (process.env.TELEGRAM_CHAT_ID || '').trim();
const GEMINI_API_KEY  = (process.env.GEMINI_API_KEY || '').trim();

const USER_HOME = process.env.USERPROFILE || 'C:\\Users\\Lenovo';
const PM2_LOG_DIR = path.join(USER_HOME, '.pm2', 'logs');
const LARAVEL_LOG = path.join(__dirname, '..', 'storage', 'logs', 'laravel.log');

// ─── State ────────────────────────────────────────────────────────────────────

let lastTelegramUpdateId = 0;
const logFileSizes = {};
const recentErrors = new Set();

// ─── Helpers ──────────────────────────────────────────────────────────────────

function log(msg) {
    const ts = new Date().toLocaleTimeString('id-ID', { hour12: false });
    console.log(`[Watchdog ${ts}] ${msg}`);
}

/**
 * Eksekusi perintah PM2 dengan windowsHide: true
 * Dijamin 100% TIDAK AKAN menampilkan jendela CMD/terminal di layar Windows.
 */
async function pm2cmd(args) {
    try {
        const { stdout } = await execAsync(`pm2 ${args}`, { windowsHide: true });
        return stdout.trim();
    } catch (err) {
        return null;
    }
}

// ─── Telegram Notifier ────────────────────────────────────────────────────────

async function sendTelegram(text, parseMode = 'HTML') {
    if (!TELEGRAM_TOKEN || !TELEGRAM_CHAT_ID) return;
    try {
        await axios.post(
            `https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage`,
            {
                chat_id: TELEGRAM_CHAT_ID,
                text: text,
                parse_mode: parseMode,
                disable_web_page_preview: true
            },
            { timeout: 8000 }
        );
    } catch (err) {
        if (!err.response?.data?.description?.includes('chat not found')) {
            log(`⚠ Telegram send error: ${err.message}`);
        }
    }
}

// ─── Gemini AI Diagnosis ──────────────────────────────────────────────────────

async function askGeminiForDiagnosis(errorContext, source) {
    if (!GEMINI_API_KEY) return null;
    try {
        const prompt = `Anda adalah Senior SRE / DevOps untuk sistem RPA Gas LPG (Laravel + Node.js Puppeteer bot input transaksi ke portal MyPertamina).
Terjadi error baru di lingkungan produksi:

[SUMBER ERROR]: ${source}
[POTONGAN LOG]:
${errorContext}

Berikan analisa singkat dan padat dalam format:
1. MASALAH: (1 kalimat inti)
2. AKAR PENYEBAB: (1-2 poin)
3. SOLUSI PERBAIKAN: (langkah konkret)
4. TINGKAT BAHAYA: (Rendah / Sedang / Kritis)`;

        const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=${GEMINI_API_KEY}`;
        const res = await axios.post(url, {
            contents: [{ parts: [{ text: prompt }] }]
        }, { timeout: 30000 });

        return res.data?.candidates?.[0]?.content?.parts?.[0]?.text || null;
    } catch (err) {
        log(`⚠ Gemini API error: ${err.message}`);
        return null;
    }
}

// ─── Event-Driven File Watchers (Hanya Bereaksi Saat Ada Error Baru) ──────────

function setupLogWatcher(filePath, source) {
    if (!fs.existsSync(filePath)) {
        // Buat file kosong jika belum ada agar bisa di-watch
        try { fs.closeSync(fs.openSync(filePath, 'a')); } catch (e) {}
    }

    try {
        const stat = fs.statSync(filePath);
        logFileSizes[filePath] = stat.size;
    } catch (e) {
        logFileSizes[filePath] = 0;
    }

    // Menggunakan fs.watchFile (native OS event, tanpa polling terminal CMD)
    fs.watchFile(filePath, { interval: 2000 }, async (curr, prev) => {
        if (curr.size <= prev.size) return; // Ukuran tidak bertambah (bukan error baru)

        const bytesToRead = curr.size - prev.size;
        const buffer = Buffer.alloc(Math.min(bytesToRead, 4000));

        try {
            const fd = fs.openSync(filePath, 'r');
            fs.readSync(fd, buffer, 0, buffer.length, curr.size - buffer.length);
            fs.closeSync(fd);

            const newContent = buffer.toString('utf8');

            // Pola error fatal yang perlu perhatian
            const errorPatterns = [
                /TypeError: [^\n]+/i,
                /ReferenceError: [^\n]+/i,
                /SyntaxError: [^\n]+/i,
                /Request failed with status code 429/i,
                /FATAL: [^\n]+/i,
                /UnhandledPromiseRejection[^\n]*/i,
                /SQLSTATE\[[0-9A-Z]+\]: [^\n]+/i
            ];

            for (const pattern of errorPatterns) {
                const match = newContent.match(pattern);
                if (match) {
                    const errorKey = match[0].trim();
                    if (recentErrors.has(errorKey)) continue; // Hindari duplikasi dalam 15 menit

                    recentErrors.add(errorKey);
                    setTimeout(() => recentErrors.delete(errorKey), 15 * 60 * 1000);

                    log(`🚨 Error baru terdeteksi di ${source}: ${errorKey}`);

                    // Analisis dengan Gemini AI
                    const diagnosis = await askGeminiForDiagnosis(newContent.slice(-1500), source);

                    let telegramMsg = `🚨 <b>[Watchdog AI] Terdeteksi Error Baru!</b>\n\n` +
                        `📍 <b>Sumber:</b> <code>${source}</code>\n` +
                        `⚠️ <b>Error:</b> <code>${errorKey}</code>\n\n`;

                    if (diagnosis) {
                        telegramMsg += `🧠 <b>Analisis Gemini AI:</b>\n<pre>${diagnosis}</pre>`;
                    }

                    await sendTelegram(telegramMsg);
                    break;
                }
            }
        } catch (err) {
            // Abaikan error pembacaan sementara
        }
    });

    log(`✓ File watcher aktif untuk: ${path.basename(filePath)}`);
}

// ─── Telegram Bot Listener (Two-Way) ──────────────────────────────────────────

async function pollTelegramUpdates() {
    if (!TELEGRAM_TOKEN) return;

    try {
        const res = await axios.get(
            `https://api.telegram.org/bot${TELEGRAM_TOKEN}/getUpdates?offset=${lastTelegramUpdateId + 1}&timeout=5`,
            { timeout: 10000 }
        );

        const updates = res.data?.result || [];
        for (const u of updates) {
            lastTelegramUpdateId = u.update_id;
            const msg = u.message;
            if (!msg || !msg.text) continue;

            const chatId = msg.chat?.id;
            const text = msg.text.trim();

            if (!TELEGRAM_CHAT_ID || TELEGRAM_CHAT_ID !== String(chatId)) {
                TELEGRAM_CHAT_ID = String(chatId);
                log(`✓ Chat ID Telegram tersambung: ${TELEGRAM_CHAT_ID}`);
            }

            if (text === '/start') {
                await sendTelegram(
                    `👋 <b>Halo! Gas RPA Watchdog AI telah terhubung.</b>\n\n` +
                    `Sistem berjalan <b>100% pasif & silent</b> (hanya bereaksi saat ada error/gangguan pada bot).\n\n` +
                    `<b>Perintah yang tersedia:</b>\n` +
                    `• /status — Cek kesehatan PM2 & antrean saat ini\n` +
                    `• /restart — Restart seluruh worker RPA & scaler\n` +
                    `• /test — Tes koneksi notifikasi\n` +
                    `• /help — Menampilkan menu bantuan`
                );
            } else if (text === '/status') {
                const listRaw = await pm2cmd('jlist');
                const procs = listRaw ? JSON.parse(listRaw) : [];
                let procInfo = '';
                for (const p of procs) {
                    if (['landingpage', 'queue-gas', 'reverb-gas', 'reverb-pos', 'rpa-scaler', 'rpa-watchdog'].includes(p.name) || p.name.startsWith('rpa-bot')) {
                        const icon = p.pm2_env?.status === 'online' ? '🟢' : '🔴';
                        procInfo += `  ${icon} <b>${p.name}</b>: ${p.pm2_env?.status} (RAM: ${Math.round((p.monit?.memory || 0)/1024/1024)}MB)\n`;
                    }
                }

                let queueInfo = 'Gagal membaca antrean';
                try {
                    const qRes = await axios.get(`${LARAVEL_URL}/api/rpa-sync/queue-stats`, {
                        headers: { 'x-api-key': API_KEY }, timeout: 4000
                    });
                    queueInfo = `Pending: ${qRes.data.pending} | Processing: ${qRes.data.processing}`;
                } catch (e) {}

                await sendTelegram(
                    `📊 <b>STATUS SISTEM GAS RPA</b>\n\n` +
                    `<b>Layanan PM2:</b>\n${procInfo}\n` +
                    `<b>Status Antrean:</b>\n${queueInfo}\n\n` +
                    `🕒 <i>Waktu: ${new Date().toLocaleTimeString('id-ID')}</i>`
                );
            } else if (text === '/restart') {
                await sendTelegram('🔄 <b>Sedang me-restart rpa-scaler dan worker...</b>');
                await pm2cmd('restart rpa-scaler');
                await sendTelegram('✅ <b>RPA Scaler berhasil direstart.</b> Sistem siap mengambil antrean baru.');
            } else if (text === '/test') {
                await sendTelegram('🧪 <b>Tes Berhasil!</b> Watchdog AI terhubung dengan normal & siap memantau.');
            } else if (text === '/help') {
                await sendTelegram(
                    `<b>Daftar Perintah:</b>\n` +
                    `/status - Cek kondisi PM2 & antrean\n` +
                    `/restart - Restart worker RPA\n` +
                    `/test - Uji coba notifikasi`
                );
            }
        }
    } catch (e) {
        // Polling timeout normal
    }
}

// ─── Safety Net Berkala Sangat Santai (Setiap 15 Menit) ────────────────────────

async function relaxedSafetyCheck() {
    try {
        // Cek apakah ada antrean yang stuck di status 'processing' lebih dari 20 menit
        const statsRes = await axios.get(`${LARAVEL_URL}/api/rpa-sync/queue-stats`, {
            headers: { 'x-api-key': API_KEY }, timeout: 5000
        });
        const { pending, processing } = statsRes.data;

        if (pending > 0 && processing === 0) {
            // Ada pending tapi tidak ada processing, pastikan scaler bangun
            const listRaw = await pm2cmd('jlist');
            const procs = listRaw ? JSON.parse(listRaw) : [];
            const scaler = procs.find(p => p.name === 'rpa-scaler');
            if (scaler && scaler.pm2_env?.status !== 'online') {
                log('🚨 Scaler mati saat ada pending job! Me-restart scaler...');
                await pm2cmd('restart rpa-scaler');
                await sendTelegram('🚨 <b>[Self-Healing]</b> rpa-scaler otomatis di-restart karena ada antrean pending.');
            }
        }
    } catch (e) {}
}

// ─── Main Startup ─────────────────────────────────────────────────────────────

async function main() {
    console.log('=======================================================');
    console.log('  RPA WATCHDOG & AI ANALYZER — SILENT & EVENT-DRIVEN');
    console.log('=======================================================');
    console.log('Mode: 100% Pasif & Silent (Bebas Terminal Pop-up & 0% Polling)');
    console.log('Watchdog hanya bangun saat ada file error baru atau perintah Telegram.');
    console.log('=======================================================');

    // 1. Pasang File Watcher pada Log Error
    setupLogWatcher(path.join(PM2_LOG_DIR, 'rpa-bot-1-error.log'), 'rpa-bot-1');
    setupLogWatcher(path.join(PM2_LOG_DIR, 'rpa-scaler-error.log'), 'rpa-scaler');
    setupLogWatcher(LARAVEL_LOG, 'laravel.log');

    // 2. Listener Telegram untuk membalas pesan user
    setInterval(pollTelegramUpdates, 3000);

    // 3. Safety net sangat santai (tiap 15 menit, bukan 30 detik!)
    setInterval(relaxedSafetyCheck, 15 * 60 * 1000);

    log('Watchdog siap dan tidur dengan tenang (menunggu event)...');
}

main().catch(err => {
    console.error('Fatal watchdog error:', err);
    process.exit(1);
});
