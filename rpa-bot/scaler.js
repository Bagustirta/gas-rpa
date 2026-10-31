/**
 * RPA Bot Auto-Scaler Daemon
 *
 * ── Cara Kerja ─────────────────────────────────────────────────────────────
 *
 * PUSH (utama): Laravel langsung POST ke /trigger setiap kali ada RpaSync
 *   baru dibuat. Scaler langsung bangun dan spawn worker. Zero polling saat idle.
 *
 * PULL (fallback): Polling setiap FALLBACK_INTERVAL_MS (default 5 menit)
 *   sebagai jaga-jaga jika trigger dari Laravel gagal (restart scaler, dsb).
 *
 * Prinsip scaling: 1 job pending = 1 worker langsung jalan.
 *   pending=1 → 1 worker
 *   pending=2 → 2 worker  (paralel, tidak nunggu)
 *   pending=5 → 5 worker  (semua jalan sekaligus)
 *
 * Worker di-spawn via `pm2 start` — tidak perlu pre-define di ecosystem.json.
 * Setelah job habis dan idle IDLE_TIMEOUT_MIN menit, worker di-delete (bebaskan RAM).
 */

'use strict';

const axios    = require('axios');
const http     = require('http');
const { exec } = require('child_process');
const util     = require('util');
require('dotenv').config();

const execAsync = util.promisify(exec);

// ─── Konfigurasi ──────────────────────────────────────────────────────────────

const LARAVEL_URL  = (process.env.LARAVEL_APP_URL || 'http://localhost:8000').trim();
const API_KEY      = process.env.LPG_API_KEY      || 'lpg-secret-token-12345';
const BOT_SCRIPT   = require('path').join(__dirname, 'bot.js');

/**
 * Batas atas worker yang boleh berjalan bersamaan.
 * Formula: MAX_WORKERS = Math.floor(RAM_tersedia_GB * 1024 / 500)
 */
const MAX_WORKERS = parseInt(process.env.MAX_WORKERS || '10');

/**
 * Port lokal tempat scaler mendengarkan trigger dari Laravel.
 * Laravel POST ke http://localhost:SCALER_PORT/trigger saat ada job baru.
 */
const SCALER_PORT = parseInt(process.env.SCALER_PORT || '3099');

/**
 * Secret key untuk validasi trigger dari Laravel agar tidak bisa ditembak sembarangan.
 */
const SCALER_SECRET = process.env.SCALER_SECRET || API_KEY;

/**
 * Interval fallback polling — hanya sebagai safety net jika trigger gagal.
 * Default 5 menit. Tidak perlu kecil karena trigger yang jadi andalan utama.
 */
const FALLBACK_INTERVAL_MS = parseInt(process.env.SCALER_FALLBACK_INTERVAL || '300000'); // 5 menit

/** Menit idle tanpa job sebelum worker di-stop & hapus dari PM2 */
const IDLE_TIMEOUT_MIN = parseInt(process.env.SCALER_IDLE_TIMEOUT || '5');

// ─── State ────────────────────────────────────────────────────────────────────

/** Set ID worker yang sedang online (e.g. Set{1, 2, 3}) */
const activeWorkers = new Set();

/** Waktu antrian terakhir kali kosong (untuk hitung idle timer) */
let idleSince = null;

/** Mencegah checkAndScale berjalan bersamaan */
let isChecking = false;

// ─── HTTP Client ──────────────────────────────────────────────────────────────

const api = axios.create({
    baseURL: LARAVEL_URL,
    headers: { 'x-api-key': API_KEY, 'Accept': 'application/json' },
    timeout: 8000,
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function log(msg) {
    const ts = new Date().toLocaleTimeString('id-ID', { hour12: false });
    console.log(`[Scaler ${ts}] ${msg}`);
}

async function pm2cmd(args) {
    const { stdout } = await execAsync(`pm2 ${args}`);
    return stdout.trim();
}

async function fetchQueueStats() {
    const res = await api.get('/api/rpa-sync/queue-stats');
    return res.data; // { pending, processing, total_active }
}

// ─── Worker Lifecycle ─────────────────────────────────────────────────────────

async function spawnWorker(id) {
    log(`▲ SPAWN  rpa-bot-${id}`);
    await pm2cmd(
        `start "${BOT_SCRIPT}" --name "rpa-bot-${id}" --interpreter node ` +
        `--env WORKER_ID=${id} ` +
        `--no-autorestart`
    );
    activeWorkers.add(id);
    log(`✓ rpa-bot-${id} online. (Total aktif: ${activeWorkers.size})`);
}

async function killWorker(id) {
    log(`▼ KILL   rpa-bot-${id}`);
    try {
        await pm2cmd(`delete "rpa-bot-${id}"`);
    } catch (e) {
        // Sudah tidak ada, abaikan
    }
    activeWorkers.delete(id);
    log(`✓ rpa-bot-${id} dihapus. (Total aktif: ${activeWorkers.size})`);
}

async function reconcile(desired) {
    const current = activeWorkers.size;
    if (desired === current) return;

    if (desired > current) {
        const needed = desired - current;
        log(`Scale UP: +${needed} worker (${current} → ${desired})`);

        let spawned = 0;
        for (let id = 1; spawned < needed; id++) {
            if (!activeWorkers.has(id)) {
                await spawnWorker(id);
                spawned++;
                if (spawned < needed) {
                    await new Promise(r => setTimeout(r, 600));
                }
            }
        }
    } else {
        const toKill = current - desired;
        log(`Scale DOWN: -${toKill} worker (${current} → ${desired})`);

        const sortedIds = [...activeWorkers].sort((a, b) => b - a);
        for (let i = 0; i < toKill; i++) {
            await killWorker(sortedIds[i]);
            if (i < toKill - 1) {
                await new Promise(r => setTimeout(r, 200));
            }
        }
    }
}

// ─── Main Check Logic ─────────────────────────────────────────────────────────

async function checkAndScale(source = 'fallback') {
    // Cegah concurrent execution
    if (isChecking) {
        log(`[${source}] Sedang cek, request dilewati.`);
        return;
    }
    isChecking = true;

    try {
        const stats = await fetchQueueStats();
        const desired = Math.min(stats.total_active, MAX_WORKERS);

        log(
            `[${source}] pending: ${stats.pending} | processing: ${stats.processing} | ` +
            `aktif: ${activeWorkers.size} | dibutuhkan: ${desired}`
        );

        if (desired === 0 && activeWorkers.size > 0) {
            // Antrian kosong — tunggu idle timeout sebelum matikan semua worker
            if (!idleSince) {
                idleSince = Date.now();
                log(`Antrian kosong. Shutdown dalam ${IDLE_TIMEOUT_MIN} menit jika tetap kosong...`);
            } else {
                const idleMin = (Date.now() - idleSince) / 60000;
                if (idleMin >= IDLE_TIMEOUT_MIN) {
                    log(`Idle ${IDLE_TIMEOUT_MIN} menit. Shutdown semua worker.`);
                    await reconcile(0);
                    idleSince = null;
                } else {
                    const sisaMenit = (IDLE_TIMEOUT_MIN - idleMin).toFixed(1);
                    log(`Idle ${idleMin.toFixed(1)} mnt → shutdown dalam ${sisaMenit} menit lagi.`);
                }
            }
        } else if (desired === 0 && activeWorkers.size === 0) {
            // Benar-benar idle: tidak ada job, tidak ada worker
            if (!idleSince) idleSince = Date.now();
        } else {
            if (desired > 0) idleSince = null;
            await reconcile(desired);
        }

    } catch (err) {
        log(`⚠ Gagal cek antrian: ${err.message}`);
    } finally {
        isChecking = false;
    }
}

// ─── Webhook Server (Push Trigger) ────────────────────────────────────────────

function startWebhookServer() {
    const server = http.createServer((req, res) => {
        if (req.method !== 'POST' || req.url !== '/trigger') {
            res.writeHead(404);
            res.end('Not found');
            return;
        }

        // Validasi secret dari header
        const secret = req.headers['x-scaler-secret'];
        if (secret !== SCALER_SECRET) {
            res.writeHead(401);
            res.end('Unauthorized');
            log('⚠ Trigger ditolak — secret tidak valid.');
            return;
        }

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', message: 'Trigger diterima, scaler berjalan.' }));

        // Jalankan check segera (non-blocking)
        log('🔔 Trigger diterima dari Laravel — langsung cek antrian!');
        checkAndScale('trigger').catch(e => log(`⚠ Error saat trigger: ${e.message}`));
    });

    server.listen(SCALER_PORT, '127.0.0.1', () => {
        log(`✓ Webhook server siap di http://127.0.0.1:${SCALER_PORT}/trigger`);
    });

    server.on('error', (err) => {
        console.error(`[Scaler] Webhook server error: ${err.message}`);
    });
}

// ─── Startup ──────────────────────────────────────────────────────────────────

async function main() {
    console.log('=======================================================');
    console.log('  RPA BOT AUTO-SCALER  —  Push + Fallback Poll');
    console.log('=======================================================');
    console.log(`Laravel URL      : ${LARAVEL_URL}`);
    console.log(`Max Workers      : ${MAX_WORKERS}`);
    console.log(`Trigger Port     : ${SCALER_PORT}  (push dari Laravel)`);
    console.log(`Fallback Poll    : setiap ${FALLBACK_INTERVAL_MS / 1000}s (safety net)`);
    console.log(`Idle Timeout     : ${IDLE_TIMEOUT_MIN} menit`);
    console.log('');
    console.log('Prinsip: 1 job pending = 1 worker langsung jalan');
    console.log('  pending 1  →  1 worker  (langsung!)');
    console.log('  pending 2  →  2 worker  (paralel!)');
    console.log(`  pending N  →  min(N, ${MAX_WORKERS}) worker`);
    console.log('  idle 5 menit  →  0 worker  (RAM bebas)');
    console.log('=======================================================');

    // Deteksi worker yang mungkin masih berjalan dari sesi sebelumnya
    try {
        const list = await pm2cmd('jlist');
        const procs = JSON.parse(list);
        const existingBots = procs.filter(p =>
            /^rpa-bot-\d+$/.test(p.name) && p.pm2_env?.status === 'online'
        );
        for (const p of existingBots) {
            const id = parseInt(p.name.replace('rpa-bot-', ''));
            activeWorkers.add(id);
        }
        if (activeWorkers.size > 0) {
            log(`Ditemukan ${activeWorkers.size} worker aktif: [${[...activeWorkers].join(', ')}]`);
        } else {
            log('Tidak ada worker aktif. Siap scale dari 0.');
        }
    } catch (e) {
        log('Tidak bisa baca state PM2, mulai bersih dari 0.');
    }

    // Jalankan webhook server (menerima push dari Laravel)
    startWebhookServer();

    // Cek pertama segera saat startup
    await checkAndScale('startup');

    // Fallback polling — safety net saja, bukan andalan utama
    setInterval(() => {
        checkAndScale('fallback').catch(e => log(`⚠ Fallback error: ${e.message}`));
    }, FALLBACK_INTERVAL_MS);
}

main().catch(err => {
    console.error('[Scaler] Fatal error:', err.message);
    process.exit(1);
});
