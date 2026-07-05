const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());
const axios = require('axios');
const readline = require('readline');
require('dotenv').config();

const LARAVEL_URL = process.env.LARAVEL_APP_URL || 'http://localhost:8000';
const API_KEY = process.env.LPG_API_KEY || 'lpg-secret-token-12345';
const PERTAMINA_URL = process.env.PERTAMINA_PORTAL_URL || 'https://subsiditepat.mypertamina.id/LPG';

// Parse arguments for automatic login
function getArg(name) {
    const idx = process.argv.indexOf(name);
    if (idx !== -1 && idx + 1 < process.argv.length) {
        return process.argv[idx + 1];
    }
    for (const arg of process.argv) {
        if (arg.startsWith(name + '=')) {
            return arg.substring(name.length + 1);
        }
    }
    return null;
}
const emailRaw = getArg('--email');
const passwordRaw = getArg('--password');
const emailArg = emailRaw ? Buffer.from(emailRaw, 'base64').toString('utf8') : null;
const passwordArg = passwordRaw ? Buffer.from(passwordRaw, 'base64').toString('utf8') : null;
const userIdArg = getArg('--user-id');

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

function askQuestion(query) {
    const rl = readline.createInterface({
        input: process.stdin,
        output: process.stdout,
    });
    return new Promise(resolve => rl.question(query, ans => {
        rl.close();
        resolve(ans);
    }));
}

const api = axios.create({
    baseURL: LARAVEL_URL,
    headers: {
        'x-api-key': API_KEY,
        'Accept': 'application/json',
    }
});

function getMaskedNik(nik) {
    if (!nik || nik.length !== 16) return nik;
    return nik.substring(0, 3) + 'x'.repeat(10) + nik.substring(13);
}

async function safeClick(page, selector) {
    try {
        await page.click(selector);
    } catch (e) {
        await page.evaluate((sel) => {
            const el = document.querySelector(sel);
            if (el) el.click();
        }, selector);
    }
}

// Kirim notifikasi progress real-time ke Filament (HP user)
async function sendProgress(userId, type, message, meta = {}) {
    if (!userId) return;
    try {
        await api.post('/api/audit/progress', {
            user_id: parseInt(userId),
            type,
            message,
            meta,
        });
    } catch (e) {
        // Gagal kirim notifikasi tidak perlu menghentikan proses audit
    }
}

// Update status live di widget dashboard Filament
async function sendStatus(userId, fields = {}) {
    if (!userId) return;
    try {
        await api.post('/api/audit/status', {
            user_id: parseInt(userId),
            step: fields.step ?? 'info',
            step_label: fields.step_label ?? '',
            pangkalan_name: fields.pangkalan_name ?? undefined,
            pangkalan_index: fields.pangkalan_index ?? undefined,
            pangkalan_total: fields.pangkalan_total ?? undefined,
            nik_current: fields.nik_current ?? undefined,
            nik_total: fields.nik_total ?? undefined,
            is_running: fields.is_running !== undefined ? fields.is_running : true,
        });
    } catch (e) { /* silent */ }
}

// 1. Fungsi Pembantu: Mengisi Form & Mengklik Tombol Login
async function doLogin(page, email, password) {
    console.log(`Membuka portal Pertamina: ${PERTAMINA_URL}`);
    await page.goto(PERTAMINA_URL, { waitUntil: 'domcontentloaded' });
    await sleep(1000); // Beri jeda sejenak untuk membiarkan pengalihan URL (redirect) otomatis jika sesi aktif

    const initialUrl = page.url();
    if ((initialUrl.includes('/merchant') || initialUrl.includes('/penjualan')) && !initialUrl.includes('login') && !initialUrl.includes('auth')) {
        console.log('   ✓ Deteksi sesi aktif (sudah login). Melompati pengisian formulir.');
        return true;
    }

    console.log('[OTOMATIS] Mengisi email dan password di halaman login...');
    try {
        await page.waitForSelector('input', { timeout: 10000 });

        await page.evaluate((email, pass) => {
            const inputs = Array.from(document.querySelectorAll('input'));
            let emailInput = null;
            let passInput = null;

            for (const input of inputs) {
                const type = (input.getAttribute('type') || '').toLowerCase();
                const placeholder = (input.getAttribute('placeholder') || '').toLowerCase();
                const name = (input.getAttribute('name') || '').toLowerCase();
                const id = input.id.toLowerCase();

                if (type === 'email' || type === 'tel' || placeholder.includes('email') || placeholder.includes('username') || placeholder.includes('user') || placeholder.includes('phone') || placeholder.includes('handphone') || placeholder.includes('telp') || name.includes('email') || name.includes('username') || name.includes('phone') || name.includes('telp') || id.includes('email') || id.includes('username') || id.includes('phone') || id.includes('telp')) {
                    emailInput = input;
                }
                if (type === 'password' || placeholder.includes('password') || placeholder.includes('sandi') || placeholder.includes('pin') || name.includes('password') || name.includes('pin') || id.includes('password') || id.includes('pin')) {
                    passInput = input;
                }
            }

            if (emailInput) {
                emailInput.setAttribute('id', 'rpa-email-input');
            }
            if (passInput) {
                passInput.setAttribute('id', 'rpa-pass-input');
            }
        });

        // Hapus dan Ketik secara native menggunakan Puppeteer (agar terdeteksi React/Vue)
        await page.focus('#rpa-email-input');
        await page.keyboard.down('Control');
        await page.keyboard.press('KeyA');
        await page.keyboard.up('Control');
        await page.keyboard.press('Backspace');
        await page.type('#rpa-email-input', email, { delay: 30 });

        await page.focus('#rpa-pass-input');
        await page.keyboard.down('Control');
        await page.keyboard.press('KeyA');
        await page.keyboard.up('Control');
        await page.keyboard.press('Backspace');
        await page.type('#rpa-pass-input', password, { delay: 30 });

        console.log('✓ Mengisi email dan password secara native selesai.');

        // Tekan Enter (Banyak form React/Vue langsung submit dengan Enter)
        await page.keyboard.press('Enter');
        await sleep(500);

        // Cari tombol login & tag dengan ID unik, lalu klik secara native via Puppeteer
        const btnFound = await page.evaluate(() => {
            const candidates = Array.from(document.querySelectorAll('button, a, input[type="submit"], input[type="button"], [role="button"], .btn'));
            for (const btn of candidates) {
                const txt = (btn.textContent || btn.value || '').toLowerCase().trim();
                if (txt === 'masuk' || txt === 'login' || txt.includes('masuk') || txt.includes('login') || btn.getAttribute('type') === 'submit') {
                    const style = window.getComputedStyle(btn);
                    if (style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0') {
                        btn.setAttribute('id', 'rpa-submit-btn');
                        return true;
                    }
                }
            }
            return false;
        });

        if (btnFound) {
            await page.click('#rpa-submit-btn');
            console.log('✓ Mengklik tombol masuk secara otomatis.');
        } else {
            console.log('! Tombol login tidak ditemukan, namun tombol Enter sudah ditekan.');
        }
    } catch (e) {
        console.log('! Gagal mengisi formulir atau mengklik login otomatis:', e.message);
    }

    console.log('[PENTING] Menunggu login sukses...');
    let isLoggedIn = false;
    let attempts = 0;
    while (!isLoggedIn && attempts < 45) {
        try {
            const currentUrl = page.url();
            if (currentUrl.includes('/merchant/app') || currentUrl.includes('/merchant/dashboard') || currentUrl.includes('/penjualan')) {
                isLoggedIn = true;
            }
        } catch (e) { }
        if (!isLoggedIn) {
            await sleep(1000);
            attempts++;
        }
    }
    return isLoggedIn;
}

// 2. Fungsi Pembantu: Navigasi ke Rekap Penjualan
async function doNavigateToRekap(page) {
    console.log('[OTOMATIS] Menavigasi ke halaman Rekap Penjualan...');

    // Cari & Klik menu "Laporan Penjualan"
    let laporanBtn = await page.evaluateHandle(() => {
        const elements = Array.from(document.querySelectorAll('a, button, div, span, p, li'));
        let foundEl = null;
        for (const el of elements) {
            if (el.children.length === 0) {
                const txt = el.textContent.trim();
                if (txt === 'Laporan Penjualan') {
                    foundEl = el;
                    break;
                }
            }
        }
        if (!foundEl) {
            for (const el of elements) {
                if (el.children.length === 0) {
                    const txt = el.textContent.trim();
                    if (txt.includes('Laporan Penjualan')) {
                        foundEl = el;
                        break;
                    }
                }
            }
        }

        if (foundEl) {
            let curr = foundEl;
            while (curr && curr.tagName !== 'BODY') {
                const tag = curr.tagName.toUpperCase();
                const cls = (curr.className || '').toLowerCase();
                if (tag === 'A' || tag === 'BUTTON' || cls.includes('btn') || cls.includes('clickable') || cls.includes('menu-item') || cls.includes('nav-link')) {
                    return curr;
                }
                curr = curr.parentElement;
            }
            return foundEl;
        }
        return null;
    });

    if (laporanBtn && laporanBtn.asElement()) {
        await page.evaluate(el => el.setAttribute('id', 'rpa-laporan-btn'), laporanBtn);
        await page.click('#rpa-laporan-btn');
        await sleep(2500);
    } else {
        console.log('! Menu "Laporan Penjualan" tidak terdeteksi. Mencoba navigasi URL langsung...');
        await page.goto('https://subsiditepat.mypertamina.id/LPG/merchant/laporan', { waitUntil: 'domcontentloaded' });
        await sleep(2500);
    }

    // Cari & Klik menu "Rekap Penjualan"
    let rekapBtn = await page.evaluateHandle(() => {
        const elements = Array.from(document.querySelectorAll('a, button, div, span, p, li'));
        let foundEl = null;
        for (const el of elements) {
            if (el.children.length === 0) {
                const txt = el.textContent.trim();
                if (txt === 'Rekap Penjualan') {
                    foundEl = el;
                    break;
                }
            }
        }
        if (!foundEl) {
            for (const el of elements) {
                if (el.children.length === 0) {
                    const txt = el.textContent.trim();
                    if (txt.includes('Rekap Penjualan')) {
                        foundEl = el;
                        break;
                    }
                }
            }
        }

        if (foundEl) {
            let curr = foundEl;
            while (curr && curr.tagName !== 'BODY') {
                const tag = curr.tagName.toUpperCase();
                const cls = (curr.className || '').toLowerCase();
                if (tag === 'A' || tag === 'BUTTON' || cls.includes('btn') || cls.includes('clickable') || cls.includes('menu-item') || cls.includes('nav-link')) {
                    return curr;
                }
                curr = curr.parentElement;
            }
            return foundEl;
        }
        return null;
    });

    if (rekapBtn && rekapBtn.asElement()) {
        await page.evaluate(el => el.setAttribute('id', 'rpa-rekap-btn'), rekapBtn);
        await page.click('#rpa-rekap-btn');
        await sleep(1000);
        return true;
    }
    return false;
}

// 3. Fungsi Pembantu: Mengatur Filter Tanggal
async function doApplyDateFilter(page) {
    try {
        await page.waitForSelector('input', { timeout: 10000 });
        await sleep(1500); // Tunggu stabilisasi halaman

        console.log('[OTOMATIS] Mencari element input/picker tanggal...');
        const dateElementInfo = await page.evaluate(() => {
            const elements = Array.from(document.querySelectorAll('input, div, span, button'));
            let bestMatch = null;
            let matchReason = '';

            for (const el of elements) {
                const style = window.getComputedStyle(el);
                if (style.display === 'none' || style.visibility === 'hidden') {
                    continue;
                }

                const val = el.value || '';
                const placeholder = (el.placeholder || el.getAttribute('placeholder') || '').toLowerCase();
                const className = (el.className || '').toLowerCase();
                const id = (el.id || '').toLowerCase();

                if (el.tagName === 'INPUT' && val.includes('2026')) {
                    bestMatch = el;
                    matchReason = `INPUT dengan value: "${val}"`;
                    break;
                }
                if (placeholder.includes('rentang') || placeholder.includes('tanggal') || placeholder.includes('date') || placeholder.includes('periode')) {
                    bestMatch = el;
                    matchReason = `${el.tagName} dengan placeholder: "${placeholder}"`;
                    break;
                }
                if (className.includes('datepicker') || className.includes('date-editor') || className.includes('date-range')) {
                    bestMatch = el;
                    matchReason = `${el.tagName} dengan class: "${className}"`;
                    break;
                }
            }

            if (!bestMatch) {
                const firstInput = document.querySelector('input');
                if (firstInput) {
                    bestMatch = firstInput;
                    matchReason = 'Fallback ke input pertama di halaman';
                }
            }

            if (bestMatch) {
                bestMatch.setAttribute('id', 'rpa-date-picker-trigger');
                return {
                    success: true,
                    tagName: bestMatch.tagName,
                    id: bestMatch.id,
                    className: bestMatch.className,
                    reason: matchReason
                };
            }
            return { success: false };
        });

        console.log(`[DEBUG] Picker tanggal terdeteksi: ${JSON.stringify(dateElementInfo)}`);

        if (dateElementInfo.success) {
            await safeClick(page, '#rpa-date-picker-trigger');
            await sleep(1500);

            // Coba klik tombol "Bulan Ini" (This Month) jika ada di preset daterangepicker
            const presetFound = await page.evaluate(() => {
                const candidates = Array.from(document.querySelectorAll('li, button, div, span'));
                for (const el of candidates) {
                    if (el.children.length === 0) {
                        const txt = el.textContent.trim().toLowerCase();
                        if (txt === 'bulan ini' || txt === 'this month') {
                            const style = window.getComputedStyle(el);
                            if (style.display !== 'none' && style.visibility !== 'hidden') {
                                el.setAttribute('id', 'rpa-preset-bulan-ini');
                                return true;
                            }
                        }
                    }
                }
                return false;
            });

            if (presetFound) {
                console.log('✓ Menemukan preset "Bulan Ini". Mengklik...');
                await safeClick(page, '#rpa-preset-bulan-ini');
                await sleep(1000);
            } else {
                // Klik tanggal 1 secara native/safe (Mencari dari kanan/bawah DOM agar tidak salah klik bulan lalu di dual-calendar)
                const day1Found = await page.evaluate(() => {
                    const candidates = Array.from(document.querySelectorAll('div, td, span, button, p')).reverse();
                    for (const el of candidates) {
                        if (el.children.length === 0 && el.textContent.trim() === '1') {
                            const className = (el.className || '').toLowerCase();
                            if (className.includes('disabled') || className.includes('muted') || className.includes('old') || className.includes('new') || className.includes('outside') || className.includes('next-month') || className.includes('prev-month')) {
                                continue;
                            }

                            let target = el;
                            let curr = el;
                            while (curr && curr.tagName !== 'BODY') {
                                const tag = curr.tagName.toUpperCase();
                                const cls = (curr.className || '').toLowerCase();
                                if (tag === 'TD' || tag === 'BUTTON' || cls.includes('cell') || cls.includes('day') || cls.includes('date')) {
                                    target = curr;
                                    break;
                                }
                                curr = curr.parentElement;
                            }

                            const targetCls = (target.className || '').toLowerCase();
                            if (targetCls.includes('disabled') || targetCls.includes('muted') || targetCls.includes('outside') || targetCls.includes('prev-month') || targetCls.includes('next-month')) {
                                continue;
                            }

                            const style = window.getComputedStyle(target);
                            if (style.display !== 'none' && style.visibility !== 'hidden') {
                                target.setAttribute('id', 'rpa-day-1');
                                return true;
                            }
                        }
                    }
                    return false;
                });

                if (day1Found) {
                    console.log('✓ Menemukan Tanggal 1 di kalender (Current Month). Mengklik...');
                    await safeClick(page, '#rpa-day-1');
                    await sleep(1200);
                } else {
                    console.log('! Gagal menemukan Tanggal 1 di kalender.');
                }

                // Klik tanggal hari ini secara native/safe
                const todayDayStr = String(new Date().getDate());
                const todayFound = await page.evaluate((todayStr) => {
                    const candidates = Array.from(document.querySelectorAll('div, td, span, button, p')).reverse();
                    for (const el of candidates) {
                        if (el.children.length === 0 && el.textContent.trim() === todayStr) {
                            const className = (el.className || '').toLowerCase();
                            if (className.includes('disabled') || className.includes('muted') || className.includes('old') || className.includes('new') || className.includes('outside') || className.includes('next-month') || className.includes('prev-month')) {
                                continue;
                            }

                            let target = el;
                            let curr = el;
                            while (curr && curr.tagName !== 'BODY') {
                                const tag = curr.tagName.toUpperCase();
                                const cls = (curr.className || '').toLowerCase();
                                if (tag === 'TD' || tag === 'BUTTON' || cls.includes('cell') || cls.includes('day') || cls.includes('date')) {
                                    target = curr;
                                    break;
                                }
                                curr = curr.parentElement;
                            }

                            const targetCls = (target.className || '').toLowerCase();
                            if (targetCls.includes('disabled') || targetCls.includes('muted') || targetCls.includes('outside') || targetCls.includes('prev-month') || targetCls.includes('next-month')) {
                                continue;
                            }

                            const style = window.getComputedStyle(target);
                            if (style.display !== 'none' && style.visibility !== 'hidden') {
                                target.setAttribute('id', 'rpa-day-today');
                                return true;
                            }
                        }
                    }
                    return false;
                }, todayDayStr);

                if (todayFound) {
                    console.log(`✓ Menemukan Tanggal Hari Ini (${todayDayStr}) di kalender. Mengklik...`);
                    await safeClick(page, '#rpa-day-today');
                    await sleep(1000);
                } else {
                    console.log(`! Gagal menemukan Tanggal Hari Ini (${todayDayStr}) di kalender.`);
                }
            } // TUTUP ELSE DARI presetFound

            // Klik apply jika ada
            const applyFound = await page.evaluate(() => {
                const buttons = Array.from(document.querySelectorAll('button, a, span, div'));
                for (const btn of buttons) {
                    const txt = btn.textContent.trim().toLowerCase();
                    if (txt === 'terapkan' || txt === 'apply' || txt === 'ok' || txt === 'setuju') {
                        btn.setAttribute('id', 'rpa-apply-date-btn');
                        return true;
                    }
                }
                return false;
            });

            if (applyFound) {
                console.log('✓ Menemukan tombol Terapkan/Apply. Mengklik...');
                await safeClick(page, '#rpa-apply-date-btn');
                await sleep(1000);
            }

            console.log('✓ Filter rentang tanggal selesai diproses.');
            return true;
        } else {
            console.log('! Gagal mencari input tanggal.');
        }
    } catch (dateErr) {
        console.log('! Gagal mengatur filter tanggal:', dateErr.message);
    }
    return false;
}

async function run() {
    console.log('===================================================');
    console.log('       BOT RPA AUDIT KUOTA LPG MYPERTAMINA         ');
    console.log('===================================================');
    console.log(`Menghubungkan ke Laravel: ${LARAVEL_URL}`);

    // Start background heartbeat to Laravel
    let heartbeatInterval = null;
    if (userIdArg) {
        heartbeatInterval = setInterval(async () => {
            try {
                await api.post('/api/audit/heartbeat', {
                    user_id: parseInt(userIdArg),
                });
            } catch (e) {
                // Silent catch
            }
        }, 3000); // Setiap 3 detik
    }

    let pelanggans = [];
    let pangkalanList = [];

    if (userIdArg) {
        // 1. Ambil daftar pelanggan aktif milik user
        try {
            const response = await api.get('/api/all-pelanggan-aktif', {
                params: { user_id: userIdArg }
            });
            pelanggans = response.data.data;
            console.log(`✓ Berhasil memuat ${pelanggans.length} pelanggan aktif dari Laravel.`);
        } catch (err) {
            console.error('✗ Gagal memuat data pelanggan dari Laravel:', err.message);
            process.exit(1);
        }

        // 2. Ambil daftar pangkalan milik user
        try {
            const response = await api.get('/api/akun-pangkalan/aktif', {
                params: { user_id: userIdArg }
            });
            pangkalanList = response.data.data;
            console.log(`✓ Berhasil memuat ${pangkalanList.length} akun pangkalan dari Laravel.`);
        } catch (err) {
            console.error('✗ Gagal memuat akun pangkalan dari Laravel:', err.message);
            process.exit(1);
        }
    } else if (emailArg && passwordArg) {
        // Fallback ke single-pangkalan audit (legacy)
        try {
            const response = await api.get('/api/all-pelanggan-aktif', {
                params: { email_pangkalan: emailRaw }
            });
            pelanggans = response.data.data;
            console.log(`✓ Berhasil memuat ${pelanggans.length} pelanggan aktif dari Laravel.`);
        } catch (err) {
            console.error('✗ Gagal memuat data pelanggan dari Laravel:', err.message);
            process.exit(1);
        }

        pangkalanList = [{
            id: null,
            nama_pangkalan: 'Pangkalan (Manual)',
            email: emailArg,
            password: passwordArg
        }];
    } else {
        console.error('✗ Gagal: Argumen --user-id atau (--email dan --password) diperlukan.');
        process.exit(1);
    }

    if (pelanggans.length === 0) {
        console.log('Tidak ada pelanggan aktif untuk diaudit.');
        process.exit(0);
    }

    if (pangkalanList.length === 0) {
        console.log('Tidak ada akun pangkalan untuk diaudit.');
        process.exit(0);
    }

    // Loop untuk tiap pangkalan
    for (let pIdx = 0; pIdx < pangkalanList.length; pIdx++) {
        const pangkalan = pangkalanList[pIdx];
        const emailRawForPangkalan = Buffer.from(pangkalan.email).toString('base64');
        const merchantName = pangkalan.nama_pangkalan;

        console.log(`\n===================================================`);
        console.log(`[Pangkalan ${pIdx + 1}/${pangkalanList.length}] Audit Pangkalan: ${merchantName}`);
        console.log(`===================================================`);

        await sendProgress(userIdArg, 'pangkalan',
            `[${pIdx + 1}/${pangkalanList.length}] Memulai audit: ${merchantName}`,
            { pangkalan: merchantName, index: pIdx + 1, total: pangkalanList.length }
        );
        await sendStatus(userIdArg, {
            step: 'pangkalan',
            step_label: `[${pIdx + 1}/${pangkalanList.length}] Memulai audit: ${merchantName}`,
            pangkalan_name: merchantName,
            pangkalan_index: pIdx + 1,
            pangkalan_total: pangkalanList.length,
            nik_current: 0,
            nik_total: pelanggans.length,
            is_running: true,
        });

        console.log('\nMembuka browser...');
        const browser = await puppeteer.launch({
            headless: true, // Jalan gaib di background
            defaultViewport: null,
            args: ['--start-maximized', '--no-sandbox', '--disable-setuid-sandbox'],
            userDataDir: './user_data'
        });

        const page = (await browser.pages())[0];

        // Listener respons untuk mendeteksi error HTTP (seperti rate limit 429 atau token expired 401)
        page.on('response', (res) => {
            try {
                const parsedUrl = new URL(res.url());
                if (parsedUrl.hostname.includes('mypertamina.id')) {
                    const status = res.status();
                    if (status !== 200 && status !== 304 && status !== 204 && status !== 301 && status !== 302) {
                        console.warn(`   ⚠️ Respons server MyPertamina: [${status}] pada URL: ${res.url()}`);
                    }
                }
            } catch (e) {
                // Abaikan URL tidak valid
            }
        });

        try {
            // Jalankan login awal
            await sendProgress(userIdArg, 'login', `Login ke MyPertamina untuk "${merchantName}"...`);
            await sendStatus(userIdArg, { step: 'login', step_label: `Login ke MyPertamina — ${merchantName}...`, pangkalan_name: merchantName });
            const loginOk = await doLogin(page, pangkalan.email, pangkalan.password);
            if (!loginOk) {
                console.log('✗ Gagal login awal pada pangkalan ini. Melompati...');
                await sendProgress(userIdArg, 'error', `✗ Login gagal untuk "${merchantName}". Melompati...`);
                await sendStatus(userIdArg, { step: 'error', step_label: `✗ Login gagal untuk "${merchantName}"`, is_running: false });
                await browser.close();
                continue;
            }
            await sendProgress(userIdArg, 'login', `✓ Login berhasil untuk "${merchantName}"`);
            await sendStatus(userIdArg, { step: 'login', step_label: `✓ Login berhasil — ${merchantName}` });

            // Jalankan navigasi awal
            await sendProgress(userIdArg, 'navigate', `Menavigasi ke Rekap Penjualan - ${merchantName}...`);
            await sendStatus(userIdArg, { step: 'navigate', step_label: `Membuka Rekap Penjualan — ${merchantName}...` });
            await doNavigateToRekap(page);
            await sendProgress(userIdArg, 'navigate', `✓ Berhasil membuka halaman Rekap Penjualan`);
            await sendStatus(userIdArg, { step: 'navigate', step_label: `✓ Rekap Penjualan terbuka` });

            // Jalankan filter tanggal awal
            await sendProgress(userIdArg, 'filter', `Mengatur filter tanggal 1 s/d hari ini...`);
            await sendStatus(userIdArg, { step: 'filter', step_label: `Mengatur filter tanggal 1 s/d hari ini...` });
            await doApplyDateFilter(page);
            await sendProgress(userIdArg, 'filter', `✓ Filter tanggal berhasil diterapkan`);
            await sendStatus(userIdArg, { step: 'filter', step_label: `✓ Filter tanggal berhasil diterapkan` });

            console.log(`\nMemulai proses audit kuota untuk Pangkalan: "${merchantName}"...`);
            await sendProgress(userIdArg, 'info',
                `Mulai cek kuota ${pelanggans.length} pelanggan di "${merchantName}"`,
                { total: pelanggans.length }
            );
            let alreadyLimitedCount = 0;
            let newlyLimitedCount = 0;
            let stillAmanCount = 0;

            for (let i = 0; i < pelanggans.length; i++) {
                const p = pelanggans[i];
                const progress = `[${i + 1}/${pelanggans.length}]`;

                try {
                    // PROTEKSI MANDIRI (SELF-HEALING): Cek apakah terpental ke halaman login
                    const currentUrl = page.url();
                    if (currentUrl.includes('/login') || !currentUrl.includes('/merchant/')) {
                        console.log(`\n${progress} ⚠️ Sesi terputus/terlogout secara otomatis!`);
                        console.log('Mencoba masuk kembali dan memulihkan sesi...');
                        await sendProgress(userIdArg, 'login', `⚠️ Sesi terputus di ${progress}. Login ulang otomatis...`);

                        const restoredLogin = await doLogin(page, pangkalan.email, pangkalan.password);
                        if (!restoredLogin) {
                            console.log('✗ Gagal masuk kembali. Menunggu 5 detik dan mencoba sekali lagi...');
                            await sleep(5000);
                            const finalRetry = await doLogin(page, pangkalan.email, pangkalan.password);
                            if (!finalRetry) {
                                console.log('✗ Pemulihan sesi gagal total. Melompati sisa audit pangkalan ini.');
                                await sendProgress(userIdArg, 'error', `✗ Pemulihan sesi gagal total di "${merchantName}". Melompati sisa.`);
                                break;
                            }
                        }

                        console.log('Memulihkan navigasi rekap penjualan...');
                        await sendProgress(userIdArg, 'navigate', `♻️ Memulihkan navigasi setelah sesi terputus...`);
                        await doNavigateToRekap(page);

                        console.log('Memulihkan filter tanggal...');
                        await sendProgress(userIdArg, 'filter', `♻️ Memulihkan filter tanggal setelah sesi terputus...`);
                        await doApplyDateFilter(page);
                        await sendProgress(userIdArg, 'info', `✓ Sesi berhasil dipulihkan. Melanjutkan dari ${progress}`);
                    }

                    // Tutup modal/dialog error jika ada yang menghalangi layar
                    const closedModal = await page.evaluate(() => {
                        const buttons = Array.from(document.querySelectorAll('button, span, a, div[role="button"]'));
                        for (const btn of buttons) {
                            const txt = (btn.textContent || '').trim().toUpperCase();
                            if (txt === 'TUTUP' || txt === 'OK' || txt === 'KEMBALI' || txt === 'BATAL') {
                                const rect = btn.getBoundingClientRect();
                                if (rect.width > 0 && rect.height > 0) {
                                    btn.click();
                                    return true;
                                }
                            }
                        }
                        return false;
                    });
                    if (closedModal) {
                        console.log('   ⚠️ Mendeteksi dan menutup popup/modal yang menghalangi layar.');
                        await sleep(1500);
                    }

                    // Cari input pencarian secara dinamis & tag
                    await page.evaluate(() => {
                        const inputs = Array.from(document.querySelectorAll('input'));
                        for (const input of inputs) {
                            const placeholder = (input.placeholder || '').toLowerCase();
                            if (placeholder.includes('cari data') || placeholder.includes('nik') || placeholder.includes('search')) {
                                input.setAttribute('id', 'rpa-search-input');
                                return;
                            }
                        }
                    });

                    const searchInput = await page.$('#rpa-search-input');
                    if (!searchInput) {
                        console.log(`${progress} ! Input pencarian NIK tidak ditemukan di halaman. Menunggu...`);
                        await sleep(2000);
                        i--;
                        continue;
                    }

                    // Bersihkan input secara native agar React mendeteksi perubahan
                    await searchInput.click({ clickCount: 3 });
                    await page.keyboard.press('Backspace');
                    await sleep(400); // tunggu list kembali utuh (reset)

                    // Ketik NIK baru
                    console.log(`${progress} Memeriksa NIK: ${p.nik}...`);
                    await page.keyboard.type(p.nik);

                    // KUNCI UTAMA: Karena MyPertamina auto-filter tanpa Enter,
                    // kita cukup menunggu 1.5 detik sampai proses filternya benar-benar selesai
                    // dan menyisakan HANYA 1 customer di layar.
                    await sleep(1500);

                    // Sekarang layar pasti hanya menampilkan 1 orang (atau Kosong)
                    const prefix = p.nik.substring(0, 3);
                    const suffix = p.nik.substring(13);

                    const finalTabungCount = await page.evaluate((pre, suf) => {
                        const txt = document.body.textContent || '';
                        if (txt.includes('Data Penjualan Kosong') || txt.includes('Data penjualan tidak tersedia') || txt.includes('Tidak ada data')) {
                            return 0;
                        }

                        // JAMINAN MUTU: Kita hanya mengambil angka JIKA ada NIK yang cocok persis.
                        // Ini mencegah bot membaca angka milik pelanggan lain saat koneksi lambat dan filter belum tereksekusi.
                        const exactRegex = new RegExp('^' + pre + '.{10}' + suf + '$');
                        const all = Array.from(document.querySelectorAll('*'));

                        let targetNikNode = null;
                        for (const el of all) {
                            if (el.children.length === 0 && exactRegex.test(el.textContent.trim())) {
                                targetNikNode = el;
                                break; // Ketemu NIK yang persis
                            }
                        }

                        // Jika NIK tidak ada di layar (karena memang tidak ada di Pangkalan ini), kembalikan 0
                        if (!targetNikNode) {
                            return 0;
                        }

                        // NIK ketemu, sekarang kita naik ke kotak/card miliknya, dan cari tulisan "N Tabung" KHUSUS di kotak itu saja.
                        let container = targetNikNode.parentElement;
                        for (let level = 0; level < 8 && container && container !== document.body; level++) {
                            const leaves = Array.from(container.querySelectorAll('*')).filter(n => n.children.length === 0);
                            for (const leaf of leaves) {
                                const text = leaf.textContent.trim();
                                const m = text.match(/^(\d+)\s*Tabung/i);
                                if (m) {
                                    return parseInt(m[1], 10);
                                }
                            }
                            container = container.parentElement;
                        }

                        return 0;
                    }, prefix, suffix);

                    const tabungCount = finalTabungCount;

                    const response = await api.post('/api/pelanggan/sync-quota', {
                        nik: p.nik,
                        count: tabungCount,
                        pangkalan: merchantName,
                        email_pangkalan: emailRawForPangkalan
                    });

                    const { was_full, is_now_full, total_sebelum, total_sesudah } = response.data;

                    // Kirim notifikasi per-NIK setiap 10 pelanggan agar tidak spam
                    if ((i + 1) % 10 === 0 || i === 0) {
                        await sendProgress(userIdArg, 'cek_nik',
                            `Cek NIK ${progress} di "${merchantName}" — ${tabungCount} tabung, Total: ${total_sesudah}`,
                            { current: i + 1, total: pelanggans.length, pangkalan: merchantName, tabung: tabungCount }
                        );
                    }
                    // Update widget status setiap 5 NIK agar server tidak overload (php artisan serve bersifat single-threaded)
                    if ((i + 1) % 5 === 0 || i === 0 || i === pelanggans.length - 1) {
                        await sendStatus(userIdArg, {
                            step: 'cek_nik',
                            step_label: `Mengecek NIK pelanggan ${i + 1}/${pelanggans.length} di "${merchantName}"`,
                            nik_current: i + 1,
                            nik_total: pelanggans.length,
                        });
                    }

                    if (is_now_full) {
                        if (was_full) {
                            console.log(`   ➔ NIK ${p.nik}: Pembelian di "${merchantName}" = ${tabungCount}. Total = ${total_sesudah} (Sudah Limit sebelum audit).`);
                            alreadyLimitedCount++;
                        } else {
                            console.log(`   ➔ NIK ${p.nik}: Pembelian di "${merchantName}" = ${tabungCount}. Total = ${total_sesudah} (BARU TERKUNCI).`);
                            newlyLimitedCount++;
                        }
                    } else {
                        console.log(`   ➔ NIK ${p.nik}: Pembelian di "${merchantName}" = ${tabungCount}. Total = ${total_sesudah} (Aman).`);
                        stillAmanCount++;
                    }

                } catch (err) {
                    console.error(`   ✗ Gagal memproses NIK ${p.nik}:`, err.message);
                }

                await sleep(150);
            }

            const summaryMsg = `✅ Selesai "${merchantName}": Limit=${alreadyLimitedCount + newlyLimitedCount} (${newlyLimitedCount} baru), Aman=${stillAmanCount}`;
            console.log(`\n✓ Audit Pangkalan "${merchantName}" selesai.`);
            console.log(`- Sudah Limit Sebelum Audit   : ${alreadyLimitedCount} pelanggan`);
            console.log(`- Baru Terkunci (Limit Habis) : ${newlyLimitedCount} pelanggan`);
            console.log(`- Masih Aman (< 4 Tabung)     : ${stillAmanCount} pelanggan`);
            await sendProgress(userIdArg, 'selesai', summaryMsg, {
                pangkalan: merchantName,
                sudah_limit: alreadyLimitedCount,
                baru_limit: newlyLimitedCount,
                aman: stillAmanCount
            });
            await sendStatus(userIdArg, {
                step: 'selesai',
                step_label: summaryMsg,
                pangkalan_index: pIdx + 1,
                nik_current: pelanggans.length,
                nik_total: pelanggans.length,
                // Tetap is_running=true jika masih ada pangkalan berikutnya
                is_running: pIdx + 1 < pangkalanList.length,
            });

        } catch (err) {
            console.error('Terjadi kesalahan pangkalan:', err.message);
            await sendProgress(userIdArg, 'error', `✗ Error di "${merchantName}": ${err.message}`);
            await sendStatus(userIdArg, { step: 'error', step_label: `✗ Error: ${err.message}`, is_running: false });
        } finally {
            await browser.close();
        }
    }

    await sendProgress(userIdArg, 'selesai', `🎉 Semua audit pangkalan telah selesai!`, { total_pangkalan: pangkalanList.length });
    await sendStatus(userIdArg, {
        step: 'selesai',
        step_label: `🎉 Semua audit ${pangkalanList.length} pangkalan telah selesai!`,
        pangkalan_index: pangkalanList.length,
        pangkalan_total: pangkalanList.length,
        nik_current: pelanggans.length,
        nik_total: pelanggans.length,
        is_running: false,
    });
    console.log('\n===================================================');
    console.log('         SEMUA AUDIT PANGKALAN SELESAI             ');
    console.log('===================================================');
    process.exit(0);
}

run();
