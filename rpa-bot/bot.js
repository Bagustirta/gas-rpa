const puppeteer = require('puppeteer');
const axios = require('axios');
const readline = require('readline');
require('dotenv').config();

const LARAVEL_URL = process.env.LARAVEL_APP_URL || 'http://localhost:8000';
const API_KEY = process.env.LPG_API_KEY || 'lpg-secret-token-12345';
const PERTAMINA_URL = process.env.PERTAMINA_PORTAL_URL || 'https://subsiditepat.mypertamina.id/LPG';

// Helper sleep berbasis Node.js agar aman dari Execution Context Destroyed saat page reload
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

// Variabel global untuk pengelolaan cleanup saat interupsi (Ctrl+C)
let activeBrowser = null;
let activeSyncId = null;

// Helper untuk memeriksa apakah suatu elemen terlihat di layar (visible)
async function isElementVisible(page, element) {
    try {
        return await page.evaluate((el) => {
            if (!el) return false;
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
                return false;
            }
            const rect = el.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        }, element);
    } catch (e) {
        return false;
    }
}

// Helper untuk mencari element input berdasarkan placeholder atau atribut name/id (hanya yang visible)
async function findInputByKeywords(page, keywords) {
    try {
        const inputHandle = await page.evaluateHandle((kws) => {
            const isVisible = (el) => {
                const style = window.getComputedStyle(el);
                if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
                    return false;
                }
                const rect = el.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0;
            };

            const inputs = Array.from(document.querySelectorAll('input, textarea, [contenteditable="true"]'));
            for (const kw of kws) {
                const lowercaseKw = kw.toLowerCase();
                for (const input of inputs) {
                    if (!isVisible(input)) continue;
                    const placeholder = (input.getAttribute('placeholder') || '').toLowerCase();
                    const name = (input.getAttribute('name') || '').toLowerCase();
                    const id = input.id.toLowerCase();
                    if (placeholder.includes(lowercaseKw) || name.includes(lowercaseKw) || id.includes(lowercaseKw)) {
                        return input;
                    }
                }
            }
            return null;
        }, keywords);

        return inputHandle.asElement();
    } catch (e) {
        return null;
    }
}

// Helper untuk mencari tombol/link berdasarkan teks (hanya yang visible & memprioritaskan elemen tombol/interactive leaf nodes)
async function findButtonByKeywords(page, keywords) {
    try {
        const btnHandle = await page.evaluateHandle((kws) => {
            const isVisible = (el) => {
                const style = window.getComputedStyle(el);
                if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
                    return false;
                }
                const rect = el.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0;
            };

            const candidates = Array.from(document.querySelectorAll('button, a, input[type="button"], input[type="submit"], [role="button"], .btn, .button'));
            for (const kw of kws) {
                const lowercaseKw = kw.toLowerCase();
                for (const el of candidates) {
                    if (!isVisible(el)) continue;
                    const text = (el.textContent || el.value || '').trim().toLowerCase();
                    if (text === lowercaseKw || text.includes(lowercaseKw)) {
                        return el;
                    }
                }
            }

            const allElements = Array.from(document.querySelectorAll('span, div, p, li'));
            for (const kw of kws) {
                const lowercaseKw = kw.toLowerCase();
                for (const el of allElements) {
                    if (!isVisible(el)) continue;
                    if (el.querySelector('span, div, button, a, p, li')) continue;
                    const text = (el.textContent || '').trim().toLowerCase();
                    if (text === lowercaseKw || text.includes(lowercaseKw)) {
                        return el;
                    }
                }
            }
            return null;
        }, keywords);

        return btnHandle.asElement();
    } catch (e) {
        return null;
    }
}

// Helper untuk input terminal
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

// Konfigurasi HTTP Client Axios dengan API Key
const api = axios.create({
    baseURL: LARAVEL_URL,
    headers: {
        'x-api-key': API_KEY,
        'Accept': 'application/json',
    }
});

async function run() {
    console.log('===================================================');
    console.log('       BOT RPA SINKRONISASI LPG PERTAMINA         ');
    console.log('===================================================');
    console.log(`Menghubungkan ke Laravel: ${LARAVEL_URL}`);

    try {
        // Parse arguments
        const args = process.argv.slice(2);
        let targetSyncId = null;
        for (const arg of args) {
            if (arg.startsWith('--sync-id=')) {
                targetSyncId = arg.split('=')[1];
            }
        }

        // 1. Memeriksa antrean sinkronisasi
        console.log('Memeriksa antrean sinkronisasi di database...');
        const syncUrl = targetSyncId ? `/api/rpa-sync/pending?sync_id=${targetSyncId}` : '/api/rpa-sync/pending';
        const syncResponse = await api.get(syncUrl);
        const syncJob = syncResponse.data.sync;

        if (!syncJob) {
            console.log('✓ Tidak ada antrean sinkronisasi yang cocok atau (pending) saat ini.');
            console.log('Tugas selesai.');
            return;
        }

        const syncId = syncJob.id;
        activeSyncId = syncId; // Set ke global untuk cleanup jika Ctrl+C
        console.log(`Menemukan Job ID #${syncId} (Dibuat pada: ${syncJob.created_at})`);

        // 2. Ubah status job ke 'processing'
        await api.patch(`/api/rpa-sync/${syncId}`, { status: 'processing' });
        console.log('Status antrean diubah ke: PROCESSING.');

        // 3. Ambil daftar seluruh akun pangkalan yang aktif
        console.log('Mengambil daftar akun pangkalan yang terdaftar...');
        const pangkalanUrl = (syncJob && syncJob.user_id) ? `/api/akun-pangkalan/aktif?user_id=${syncJob.user_id}` : '/api/akun-pangkalan/aktif';
        const pangkalanResponse = await api.get(pangkalanUrl);
        const pangkalanList = pangkalanResponse.data.data;

        if (pangkalanList.length === 0) {
            console.log('✗ Tidak ada akun pangkalan terdaftar di database.');
            await api.patch(`/api/rpa-sync/${syncId}`, {
                status: 'failed',
                message: 'Tidak ada akun pangkalan terdaftar untuk login.'
            });
            activeSyncId = null;
            return;
        }

        console.log(`Ditemukan ${pangkalanList.length} akun pangkalan.`);

        let successCount = 0;
        let failCount = 0;
        let completedSuccessfully = false;
        let anyPangkalanProcessed = false;

        // Loop untuk login berantai ke masing-masing pangkalan
        for (let pIdx = 0; pIdx < pangkalanList.length; pIdx++) {
            const pangkalan = pangkalanList[pIdx];
            const isLastPangkalan = (pIdx === pangkalanList.length - 1);

            console.log(`\n---------------------------------------------------`);
            console.log(`[Akun ${pIdx + 1}/${pangkalanList.length}] Login ke: ${pangkalan.nama_pangkalan}`);
            console.log(`---------------------------------------------------`);

            // Ambil data NIK transaksi yang belum dilaporkan saat ini
            console.log('Mengambil daftar transaksi terupdate...');
            const trxResponse = await api.get(`/api/nik-siap-lapor/detail?akun_pangkalan_id=${pangkalan.id}`);
            const transactions = trxResponse.data.data;

            if (transactions.length === 0) {
                console.log(`✓ Tidak ada transaksi pending untuk pangkalan: ${pangkalan.nama_pangkalan}`);
                
                // Cek apakah masih ada transaksi untuk pangkalan lain
                const checkUrl = (syncJob && syncJob.user_id) ? `/api/nik-siap-lapor/detail?user_id=${syncJob.user_id}` : '/api/nik-siap-lapor/detail';
                const trxCheck = await api.get(checkUrl);
                if (trxCheck.data.data.length === 0) {
                    console.log('✓ Semua transaksi selesai dilaporkan!');
                    completedSuccessfully = true;
                    break;
                } else {
                    console.log('Mengalihkan ke pangkalan berikutnya...');
                    continue;
                }
            }

            console.log(`Sisa transaksi siap lapor: ${transactions.length} baris.`);

            // 4. Buka Browser (Headless: false agar user bisa melihat & intervensi)
            console.log('Membuka browser otomatis...');
            const browser = await puppeteer.launch({
                headless: false,
                defaultViewport: null,
                args: ['--start-maximized']
            });
            activeBrowser = browser; // Set ke global untuk cleanup jika Ctrl+C

            const [page] = await browser.pages();
            console.log(`Membuka portal Pertamina: ${PERTAMINA_URL}`);
            await page.goto(PERTAMINA_URL, { waitUntil: 'domcontentloaded' });

            // Auto-fill login credentials if provided
            if (pangkalan.email && pangkalan.password) {
                console.log('[OTOMATIS] Mengisi nomor ponsel/email dan PIN di halaman login...');
                try {
                    await page.waitForSelector('input', { timeout: 10000 });
                    await page.evaluate(() => {
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
                            emailInput.setAttribute('data-rpa-sync', 'email');
                        }
                        if (passInput) {
                            passInput.setAttribute('data-rpa-sync', 'password');
                        }
                    });

                    // Ketik layaknya manusia untuk menembus proteksi React state
                    await page.click('[data-rpa-sync="email"]', { clickCount: 3 });
                    await page.keyboard.press('Backspace');
                    await sleep(200);
                    await page.type('[data-rpa-sync="email"]', pangkalan.email, { delay: 60 });
                    
                    await sleep(300);

                    await page.click('[data-rpa-sync="password"]', { clickCount: 3 });
                    await page.keyboard.press('Backspace');
                    await sleep(200);
                    await page.type('[data-rpa-sync="password"]', pangkalan.password, { delay: 60 });
                    
                    // Pancing event blur dan change agar tulisan error merah hilang
                    await page.evaluate(() => {
                        const emailEl = document.querySelector('[data-rpa-sync="email"]');
                        const passEl = document.querySelector('[data-rpa-sync="password"]');
                        if (emailEl) {
                            emailEl.dispatchEvent(new Event('change', { bubbles: true }));
                            emailEl.dispatchEvent(new Event('blur', { bubbles: true }));
                        }
                        if (passEl) {
                            passEl.dispatchEvent(new Event('change', { bubbles: true }));
                            passEl.dispatchEvent(new Event('blur', { bubbles: true }));
                        }
                    });

                    console.log('✓ Mengisi form login selesai.');

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
            }

            // 5. Menunggu login sukses secara otomatis
            console.log('Menunggu Anda berhasil login di browser...');
            let isLoggedIn = false;
            while (!isLoggedIn) {
                try {
                    const currentUrl = page.url();
                    if (currentUrl.includes('/merchant/app') || currentUrl.includes('/merchant/dashboard') || currentUrl.includes('/penjualan')) {
                        isLoggedIn = true;
                    }
                } catch (e) {}
                if (!isLoggedIn) {
                    await sleep(1000);
                }
            }

            console.log('✓ Mendeteksi Anda sudah berhasil login.');
            anyPangkalanProcessed = true;

            // Deteksi nama Pangkalan secara otomatis dari halaman
            let merchantName = await page.evaluate(() => {
                const divs = Array.from(document.querySelectorAll('div, span, p, h1, h2, h3, header'));
                for (const el of divs) {
                    const rect = el.getBoundingClientRect();
                    if (rect.top < 100 && rect.right > window.innerWidth - 350 && rect.width > 0 && rect.height > 0) {
                        if (el.children.length === 0) {
                            const txt = el.textContent.trim();
                            if (txt.length > 3 && txt.length < 50 && !txt.includes('\n')) {
                                return txt;
                            }
                        }
                    }
                }
                return null;
            });
            
            if (!merchantName) {
                merchantName = pangkalan.nama_pangkalan;
            }
            console.log(`Nama Pangkalan Terdeteksi: "${merchantName}"`);

            // Cek apakah stok di dashboard 0 tabung
            let isStokHabis = false;
            try {
                isStokHabis = await page.evaluate(() => {
                    const txt = document.body.textContent.toLowerCase();
                    const match = txt.match(/stok[\s\S]*?(\d+)\s*tabung/);
                    if (match) {
                        return parseInt(match[1], 10) === 0;
                    }
                    return txt.includes('stok') && (/\b0\s*tabung\b/.test(txt) || /stok:\s*0\b/.test(txt));
                });
            } catch (e) {
                console.error('Error saat memeriksa stok dashboard:', e.message);
            }

            if (isStokHabis) {
                console.log(`🔴 Stok di Pangkalan "${merchantName}" kosong/0. Beralih ke pangkalan berikutnya...`);
                console.log('Mengirim sinyal stok kosong ke Laravel untuk pangkalan ini...');
                try {
                    await api.post('/api/rpa-sync/stok-kosong', {
                        akun_pangkalan_id: pangkalan.id
                    });
                    console.log('✓ Sinyal dikirim. Sisa transaksi pangkalan ini dibersihkan.');
                } catch (e) {
                    console.error('Gagal mengirim sinyal stok kosong ke Laravel:', e.message);
                }
                await browser.close();
                activeBrowser = null;
                continue; // Lanjut ke pangkalan selanjutnya
            }

            // Klik tombol "Catat Penjualan" secara otomatis jika berada di dashboard
            try {
                const currentUrl = page.url();
                if (currentUrl.includes('/merchant/app') || currentUrl.includes('/merchant/dashboard')) {
                    console.log('Mencoba membuka halaman "Catat Penjualan" secara otomatis...');
                    await sleep(2000);
                    const catatPenjualanBtn = await findButtonByKeywords(page, ['Catat Penjualan']);
                    if (catatPenjualanBtn) {
                        await catatPenjualanBtn.click();
                        console.log('✓ Tombol "Catat Penjualan" diklik.');
                        await sleep(3000);
                    }
                }
            } catch (e) {}

            // Menunggu halaman input NIK siap secara otomatis
            console.log('Menunggu Halaman Pencatatan Transaksi siap (mencari kolom NIK)...');
            let isInputPageReady = false;
            let waitingAttempts = 0;
            while (!isInputPageReady) {
                try {
                    isInputPageReady = await page.evaluate(() => {
                        const inputs = Array.from(document.querySelectorAll('input'));
                        return inputs.some(input => {
                            const rect = input.getBoundingClientRect();
                            const isVisible = rect.width > 0 && rect.height > 0;
                            if (!isVisible) return false;

                            const placeholder = (input.getAttribute('placeholder') || '').toLowerCase();
                            const name = (input.getAttribute('name') || '').toLowerCase();
                            const id = input.id.toLowerCase();
                            return placeholder.includes('nik') || placeholder.includes('ktp') || 
                                   name.includes('nik') || name.includes('ktp') || 
                                   id.includes('nik') || id.includes('ktp');
                        });
                    });
                } catch (e) {
                    isInputPageReady = false;
                }
                
                if (!isInputPageReady) {
                    await sleep(1000);
                    waitingAttempts++;
                    if (waitingAttempts % 10 === 0) {
                        console.log('Masih menunggu halaman input transaksi terbuka...');
                    }
                }
            }

            console.log('✓ Halaman pencatatan transaksi siap. Memulai input NIK...');
            await sleep(1000);

            let pangkalanStokHabis = false;

            // 6. Jalankan perulangan input transaksi
            for (let i = 0; i < transactions.length; i++) {
                const trx = transactions[i];
                console.log(`\n[${i + 1}/${transactions.length}] Memproses NIK: ${trx.nik} (${trx.jumlah_tabung} tabung)`);

                try {
                    console.log('Menunggu halaman stabil...');
                    let isDashboardReady = false;
                    for (let attempt = 0; attempt < 20; attempt++) {
                        const pageUrl = page.url();
                        const hasDashboardUrl = pageUrl.includes('/merchant/app') || pageUrl.includes('/merchant/dashboard');
                        const hasNikInput = await page.evaluate(() => {
                            const inputs = Array.from(document.querySelectorAll('input'));
                            return inputs.some(input => {
                                const rect = input.getBoundingClientRect();
                                return rect.width > 0 && rect.height > 0 && 
                                       (input.getAttribute('placeholder') || '').toLowerCase().includes('nik');
                            });
                        });
                        
                        if (hasDashboardUrl || hasNikInput) {
                            isDashboardReady = true;
                            break;
                        }
                        await sleep(500);
                    }

                    // Pastikan modal "Catat Penjualan" terbuka jika kita berada di dashboard
                    try {
                        const currentUrl = page.url();
                        if (currentUrl.includes('/merchant/app') || currentUrl.includes('/merchant/dashboard')) {
                            let nikInputExist = await page.evaluate(() => {
                                const txt = document.body.textContent.toLowerCase();
                                const hasModalText = txt.includes('masukkan nik') || txt.includes('16 digit');
                                const inputs = Array.from(document.querySelectorAll('input'));
                                const hasVisibleInput = inputs.some(input => {
                                    const rect = input.getBoundingClientRect();
                                    return rect.width > 0 && rect.height > 0;
                                });
                                return hasModalText && hasVisibleInput;
                            });
                            
                            if (!nikInputExist) {
                                console.log('Membuka kembali modal "Catat Penjualan"...');
                                const catatPenjualanBtn = await findButtonByKeywords(page, ['Catat Penjualan']);
                                if (catatPenjualanBtn) {
                                    await catatPenjualanBtn.click();
                                    
                                    let opened = false;
                                    for (let w = 0; w < 10; w++) {
                                        opened = await page.evaluate(() => {
                                            const inputs = Array.from(document.querySelectorAll('input'));
                                            return inputs.some(input => {
                                                const rect = input.getBoundingClientRect();
                                                return rect.width > 0 && rect.height > 0 && 
                                                       (input.getAttribute('placeholder') || '').toLowerCase().includes('nik');
                                            });
                                        });
                                        if (opened) break;
                                        await sleep(500);
                                    }
                                }
                            }
                        }
                    } catch (e) {}

                    // Input NIK
                    console.log('Menginput NIK...');
                    let nikInput = null;
                    const NIK_SELECTOR = process.env.SELECTOR_NIK_INPUT;
                    if (NIK_SELECTOR && NIK_SELECTOR !== '#nik-input-id') {
                        try {
                            nikInput = await page.waitForSelector(NIK_SELECTOR, { timeout: 3000 });
                        } catch (e) {}
                    }
                    if (!nikInput) {
                        nikInput = await findInputByKeywords(page, ['nik', 'ktp', 'identity']);
                    }
                    if (!nikInput) {
                        nikInput = await page.$('input[type="text"]');
                    }

                    if (!nikInput) {
                        throw new Error('Kolom input NIK tidak ditemukan.');
                    }

                    await nikInput.click({ clickCount: 3 });
                    await page.keyboard.press('Backspace');
                    await sleep(200);
                    await nikInput.type(trx.nik, { delay: 60 });
                    await page.keyboard.press('Tab'); // Pancing onBlur alami
                    
                    // Pancing event change React (Anti-deteksi)
                    await page.evaluate((el) => {
                        if (el) {
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                            el.dispatchEvent(new Event('blur', { bubbles: true }));
                        }
                    }, nikInput);

                    let cariBtn = null;
                    const CARI_SELECTOR = process.env.SELECTOR_CARI_BUTTON;
                    if (CARI_SELECTOR && CARI_SELECTOR !== '#btn-cari-id') {
                        try {
                            cariBtn = await page.$(CARI_SELECTOR);
                        } catch (e) {}
                    }
                    if (!cariBtn) {
                        cariBtn = await findButtonByKeywords(page, ['lanjutkan penjualan', 'lanjutkan', 'cari', 'cek', 'search', 'find']);
                    }
                    if (!cariBtn) {
                        cariBtn = await page.$('button');
                    }

                    if (!cariBtn) {
                        throw new Error('Tombol pencarian NIK tidak ditemukan.');
                    }

                    await cariBtn.click();
                    console.log('Menunggu hasil pencarian NIK...');

                    let searchState = null;
                    let errorMsg = '';
                    
                    for (let attempt = 0; attempt < 20; attempt++) {
                        const pageData = await page.evaluate(() => {
                            const txt = document.body.textContent.toLowerCase();
                            
                            const isStokKosong = txt.includes('stok tabung yang dapat dijual kosong') || 
                                                 txt.includes('stok kosong') ||
                                                 txt.includes('lakukan penebusan');
                            if (isStokKosong) {
                                return { state: 'stok_kosong' };
                            }

                            const isLimit = txt.includes('melebihi batas kewajaran') || txt.includes('batas kewajaran');
                            if (isLimit) {
                                return { state: 'limit' };
                            }

                            if (txt.includes('pilihan jenis pelanggan') || txt.includes('pelanggan terdaftar')) {
                                return { state: 'modal' };
                            }

                            const hasCekPesanan = txt.includes('cek pesanan') || txt.includes('jumlah produk');
                            if (hasCekPesanan) {
                                return { state: 'qty' };
                            }

                            const isError = txt.includes('tidak terdaftar') || 
                                            txt.includes('tidak ditemukan') || 
                                            txt.includes('nik salah') ||
                                            txt.includes('belum 17 tahun') ||
                                            txt.includes('meninggal') ||
                                            txt.includes('lengkapi nib') ||
                                            txt.includes('nib sebelum');
                            if (isError) {
                                let detailedMsg = 'NIK tidak terdaftar atau terjadi error di portal Pertamina.';
                                if (txt.includes('belum 17 tahun')) {
                                    detailedMsg = 'Gagal: NIK belum 17 tahun.';
                                } else if (txt.includes('tidak terdaftar')) {
                                    detailedMsg = 'Gagal: NIK tidak terdaftar.';
                                } else if (txt.includes('tidak ditemukan')) {
                                    detailedMsg = 'Gagal: NIK tidak ditemukan.';
                                } else if (txt.includes('nik salah')) {
                                    detailedMsg = 'Gagal: Format atau nomor NIK salah.';
                                } else if (txt.includes('meninggal')) {
                                    detailedMsg = 'Gagal: Pelanggan teridentifikasi meninggal dunia.';
                                } else if (txt.includes('lengkapi nib') || txt.includes('nib sebelum')) {
                                    detailedMsg = 'Gagal: Pelanggan mikro wajib melengkapi NIB.';
                                }
                                return { state: 'error', text: detailedMsg };
                            }

                            return null;
                        });

                        if (pageData) {
                            searchState = pageData.state;
                            if (pageData.text) errorMsg = pageData.text;
                            break;
                        }
                        await sleep(500);
                    }

                    if (!searchState) {
                        throw new Error('Pencarian NIK timeout atau tidak merespon.');
                    }

                    // --- STATE STOK KOSONG (Stok Merchant Habis) ---
                    if (searchState === 'stok_kosong') {
                        console.log(`🔴 Kritis: Stok tabung di portal MyPertamina (${merchantName}) kosong!`);
                        console.log('Mengirim sinyal stok kosong ke Laravel untuk pangkalan ini...');
                        try {
                            await api.post('/api/rpa-sync/stok-kosong', {
                                akun_pangkalan_id: pangkalan.id
                            });
                            console.log('✓ Sinyal dikirim. Sisa transaksi pangkalan ini dibersihkan.');
                        } catch (e) {
                            console.error('Gagal mengirim sinyal stok kosong ke Laravel:', e.message);
                        }
                        pangkalanStokHabis = true;
                        throw new Error('FATAL: STOK_MYPERTAMINA_KOSONG');
                    }

                    // --- STATE A: LIMIT EXCEEDED ---
                    if (searchState === 'limit') {
                        console.log('⚠️ Pelanggan melebihi batas kewajaran pembelian (Limit Habis Nasional).');
                        
                        const tutupBtn = await findButtonByKeywords(page, ['TUTUP', 'BATAL']);
                        if (tutupBtn) {
                            await tutupBtn.click();
                            await sleep(1000);
                        }

                        const gantiPelangganBtn = await findButtonByKeywords(page, ['Ganti Pelanggan']);
                        if (gantiPelangganBtn) {
                            await gantiPelangganBtn.click();
                        } else {
                            await page.reload({ waitUntil: 'domcontentloaded' });
                        }

                        await api.post(`/api/gagal-lapor/${trx.id}`, {
                            message: 'Gagal: Melebihi batas kewajaran pembelian LPG 3 kg bulan ini.'
                        });
                        failCount++;
                        await sleep(1500);
                        continue;
                    }

                    // --- STATE B: ERROR/TIDAK TERDAFTAR ---
                    if (searchState === 'error') {
                        console.log(`⚠️ ${errorMsg}`);
                        
                        const tutupBtn = await findButtonByKeywords(page, ['TUTUP', 'OK', 'KEMBALI']);
                        if (tutupBtn) {
                            await tutupBtn.click();
                            await sleep(1000);
                        }

                        await api.post(`/api/gagal-lapor/${trx.id}`, {
                            message: errorMsg
                        });
                        failCount++;
                        await sleep(1500);
                        continue;
                    }

                    // --- STATE C: MODAL PEMILIHAN JENIS PELANGGAN ---
                    if (searchState === 'modal') {
                        console.log('Mendeteksi modal pemilihan jenis pelanggan. Memilih "Rumah Tangga"...');
                        const rumahTanggaOpt = await findButtonByKeywords(page, ['Rumah Tangga']);
                        if (rumahTanggaOpt) {
                            await rumahTanggaOpt.click();
                            await sleep(500);
                        }

                        const lanjutkanBtn = await findButtonByKeywords(page, ['LANJUTKAN TRANSAKSI']);
                        if (lanjutkanBtn) {
                            await lanjutkanBtn.click();
                            await sleep(1500);
                        }

                        let qtyReady = false;
                        for (let attempt = 0; attempt < 10; attempt++) {
                            const loopState = await page.evaluate(() => {
                                const txt = document.body.textContent.toLowerCase();
                                if (txt.includes('melebihi batas kewajaran') || txt.includes('batas kewajaran')) {
                                    return 'limit';
                                }
                                if (txt.includes('cek pesanan') || txt.includes('jumlah produk')) {
                                    return 'ready';
                                }
                                return 'waiting';
                            });

                            if (loopState === 'limit') {
                                throw new Error('FATAL_LIMIT_EXCEEDED');
                            } else if (loopState === 'ready') {
                                qtyReady = true;
                                break;
                            }
                            await sleep(500);
                        }

                        if (!qtyReady) {
                            throw new Error('Halaman input Qty tidak muncul setelah mengklik Lanjutkan.');
                        }
                    }

                    // --- STATE D: PROSES HALAMAN QUANTITY ---
                    if (trx.jumlah_tabung > 1) {
                        console.log(`Menyesuaikan jumlah tabung menjadi ${trx.jumlah_tabung}...`);
                        const plusBtn = await findButtonByKeywords(page, ['+']);
                        if (plusBtn) {
                            for (let click = 1; click < trx.jumlah_tabung; click++) {
                                await plusBtn.click();
                                await sleep(500);
                            }
                        }
                    }

                    const cekPesananBtn = await findButtonByKeywords(page, ['CEK PESANAN']);
                    if (!cekPesananBtn) {
                        throw new Error('Tombol "CEK PESANAN" tidak ditemukan.');
                    }
                    await cekPesananBtn.click();
                    
                    let ringkasanReady = false;
                    for (let attempt = 0; attempt < 10; attempt++) {
                        ringkasanReady = await page.evaluate(() => {
                            return document.body.textContent.toLowerCase().includes('proses penjualan');
                        });
                        if (ringkasanReady) break;
                        await sleep(500);
                    }

                    if (!ringkasanReady) {
                        throw new Error('Halaman Ringkasan Pesanan tidak muncul setelah mengklik Cek Pesanan.');
                    }

                    // PROSES PENJUALAN
                    const prosesPenjualanBtn = await findButtonByKeywords(page, ['PROSES PENJUALAN']);
                    if (!prosesPenjualanBtn) {
                        throw new Error('Tombol "PROSES PENJUALAN" tidak ditemukan.');
                    }
                    await prosesPenjualanBtn.click();
                    await sleep(3000);

                    // CAPTCHA
                    console.log('Mendeteksi Captcha Keamanan di browser.');
                    console.log('==================================================================');
                    console.log('==> [TINDAKAN ANDA] SILAKAN SELESAIKAN CAPTCHA PUZZLE DI CHROME! <==');
                    console.log('==================================================================');

                    let isCaptchaSolved = false;
                    let captchaCheckAttempts = 0;

                    while (!isCaptchaSolved) {
                        let hasCaptcha = false;
                        try {
                            hasCaptcha = await page.evaluate(() => {
                                const txt = document.body.textContent;
                                return txt.includes('Cocokan Gambar') || txt.includes('GESER tombol');
                            });
                        } catch (e) {
                            hasCaptcha = false;
                        }

                        if (!hasCaptcha) {
                            let backToInputPage = false;
                            try {
                                backToInputPage = await page.evaluate(() => {
                                    const input = document.querySelector('input[type="text"]');
                                    return !!input && !document.body.textContent.includes('PROSES PENJUALAN');
                                });
                            } catch (e) {
                                backToInputPage = false;
                            }

                            if (backToInputPage) {
                                isCaptchaSolved = true;
                                console.log('✓ Captcha diselesaikan. Transaksi berhasil disimpan.');
                            } else {
                                let isStillOnSummary = false;
                                try {
                                    isStillOnSummary = await page.evaluate(() => {
                                        return document.body.textContent.includes('PROSES PENJUALAN');
                                    });
                                } catch (e) {
                                    isStillOnSummary = false;
                                }

                                if (isStillOnSummary) {
                                    throw new Error('Captcha ditutup atau dibatalkan oleh pengguna.');
                                } else {
                                    isCaptchaSolved = true;
                                    console.log('✓ Halaman bergeser. Transaksi selesai diproses.');
                                }
                            }
                        } else {
                            await sleep(1500);
                            captchaCheckAttempts++;
                            if (captchaCheckAttempts % 8 === 0) {
                                console.log('Sedang menunggu Anda menggeser puzzle CAPTCHA...');
                            }
                        }
                    }

                    // Konfirmasi sukses ke Laravel (dengan parameter pangkalan)
                    console.log(`-> NIK ${trx.nik} terinput. Mengirim konfirmasi ke Laravel...`);
                    await api.post(`/api/konfirmasi-lapor/${trx.id}`, { pangkalan: merchantName });
                    console.log(`✓ NIK ${trx.nik} berhasil disinkronisasi.`);
                    successCount++;

                    console.log('Mengembalikan halaman ke dashboard setelah transaksi sukses...');
                    try {
                        await page.goto(PERTAMINA_URL, { waitUntil: 'domcontentloaded' });
                        await sleep(2000);
                    } catch (e) {}

                } catch (err) {
                    console.error(`✗ Gagal memproses NIK ${trx.nik}:`, err.message);

                    // --- RECOVERY: Cek apakah dipental ke halaman login ---
                    let isLoginPage = false;
                    try {
                        isLoginPage = await page.evaluate(() => {
                            const inputs = Array.from(document.querySelectorAll('input'));
                            const hasPassword = inputs.some(input => (input.getAttribute('type') || '').toLowerCase() === 'password');
                            const txt = document.body.textContent.toLowerCase();
                            const hasLoginText = txt.includes('masuk ke akun') || txt.includes('selamat datang');
                            return hasPassword || hasLoginText;
                        });
                        if (!isLoginPage && page.url().includes('login')) {
                            isLoginPage = true;
                        }
                    } catch (e) {}

                    if (isLoginPage) {
                        console.log('⚠️ Peringatan: Sesi telah berakhir (dipental ke halaman login).');
                        console.log('🔄 Memulai ulang browser untuk pangkalan ini agar dapat login kembali...');
                        // Kurangi pIdx agar outer loop memproses pangkalan ini kembali
                        pIdx--;
                        // Break loop transaksi pangkalan ini TANPA menandai NIK ini gagal
                        break;
                    }

                    // --- RECOVERY: Cek ulang jika timeout/tidak merespon ---
                    if (err.message.includes('timeout') || err.message.includes('merespon')) {
                        trx.retryCount = (trx.retryCount || 0) + 1;
                        if (trx.retryCount <= 2) {
                            console.log(`⚠️ Terjadi kendala jaringan/timeout. Mengulang NIK ini (Percobaan ${trx.retryCount}/2)...`);
                            try {
                                await page.goto(PERTAMINA_URL, { waitUntil: 'domcontentloaded' });
                                await sleep(3000);
                            } catch (e) {}
                            i--; // Mundurkan index i agar memproses NIK yang sama di iterasi berikutnya
                            continue;
                        } else {
                            console.log('❌ Gagal memproses setelah 2 kali percobaan ulang.');
                        }
                    }

                    if (err.message.includes('STOK_MYPERTAMINA_KOSONG')) {
                        pangkalanStokHabis = true;
                    } else if (err.message.includes('FATAL_LIMIT_EXCEEDED')) {
                        console.log('⚠️ Pelanggan melebihi batas kewajaran pembelian (Limit Habis Nasional). Melaporkan ke database...');
                        failCount++;
                        try {
                            await api.post(`/api/gagal-lapor/${trx.id}`, {
                                message: `Gagal: Melebihi batas kewajaran pembelian LPG 3 kg bulan ini.`
                            });
                        } catch (e) {}
                    } else {
                        failCount++;
                        try {
                            await api.post(`/api/gagal-lapor/${trx.id}`, {
                                message: `Error Bot: ${err.message}`
                            });
                        } catch (e) {}
                    }

                    if (pangkalanStokHabis) {
                        console.log('Menghentikan input pangkalan ini karena stok kosong...');
                        break; // Keluar dari loop transaksi untuk pangkalan ini
                    }

                    console.log('Mengembalikan halaman ke form pencarian NIK...');
                    try {
                        const gantiPelangganBtn = await findButtonByKeywords(page, ['Ganti Pelanggan']);
                        if (gantiPelangganBtn) {
                            await gantiPelangganBtn.click();
                            await sleep(1500);
                        } else {
                            await page.goto(PERTAMINA_URL, { waitUntil: 'domcontentloaded' });
                            await sleep(2000);
                        }
                    } catch (e) {
                        try {
                            await page.goto(PERTAMINA_URL, { waitUntil: 'domcontentloaded' });
                            await sleep(2000);
                        } catch (err2) {}
                    }
                }
            }

            // Tutup browser pangkalan saat ini
            console.log('Menutup browser pangkalan ini...');
            await browser.close();
            activeBrowser = null;

            if (pangkalanStokHabis) {
                console.log(`Stok Pangkalan "${merchantName}" habis. Mencoba masuk ke pangkalan berikutnya...`);
            } else {
                const checkUrl = (syncJob && syncJob.user_id) ? `/api/nik-siap-lapor/detail?user_id=${syncJob.user_id}` : '/api/nik-siap-lapor/detail';
                const trxCheck = await api.get(checkUrl);
                if (trxCheck.data.data.length === 0) {
                    console.log('✓ Semua transaksi selesai dilaporkan!');
                    completedSuccessfully = true;
                    break;
                }
            }
        }

        // 8. Perbarui status Sync Job di Laravel
        console.log('\nMenyelesaikan tugas sinkronisasi...');
        const statusAkhir = completedSuccessfully || (failCount === 0 && !anyPangkalanProcessed) ? 'completed' : 'failed';
        let pesanAkhir = `Sinkronisasi selesai. Berhasil: ${successCount}, Gagal: ${failCount}.`;
        if (!completedSuccessfully && failCount === 0 && successCount > 0) {
            pesanAkhir = `Sinkronisasi selesai sebagian. Berhasil: ${successCount}. Stok habis di semua akun pangkalan terdaftar.`;
        }

        await api.patch(`/api/rpa-sync/${syncId}`, {
            status: statusAkhir,
            total_records: successCount,
            message: pesanAkhir
        });

        console.log(`\n===================================================`);
        console.log(`Hasil Sinkronisasi Job #${syncId}:`);
        console.log(`- Berhasil: ${successCount}`);
        console.log(`- Gagal: ${failCount}`);
        console.log(`Status Job diperbarui menjadi: ${statusAkhir.toUpperCase()}`);
        console.log(`===================================================`);

        activeSyncId = null;

    } catch (error) {
        console.error('\n✗ Terjadi Kesalahan Utama pada Bot:', error.message);
    }
}

// Tangkap sinyal pembatalan Ctrl+C (SIGINT)
process.on('SIGINT', async () => {
    console.log('\n\n[!] Mendeteksi Ctrl+C. Menghentikan bot...');

    try {
        if (activeBrowser) {
            console.log('Menutup browser otomatisasi...');
            await activeBrowser.close();
        }

        if (activeSyncId) {
            console.log(`Mengubah status Job #${activeSyncId} di database menjadi FAILED...`);
            await api.patch(`/api/rpa-sync/${activeSyncId}`, {
                status: 'failed',
                message: 'Proses dihentikan paksa oleh pengguna di terminal (Ctrl+C).'
            });
            console.log('✓ Status job berhasil diperbarui.');
        }
    } catch (err) {
        console.error('✗ Gagal memperbarui status job di database:', err.message);
    } finally {
        process.exit(0);
    }
});

run();
