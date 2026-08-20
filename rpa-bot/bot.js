const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());
const axios = require('axios');
const readline = require('readline');
require('dotenv').config();
const { exec } = require('child_process');
const util = require('util');
const execPromise = util.promisify(exec);

const LARAVEL_URL = (process.env.LARAVEL_APP_URL || process.env.APP_URL || 'http://localhost:8000').trim();
const API_KEY = process.env.LPG_API_KEY || 'lpg-secret-token-12345';
const PERTAMINA_URL = process.env.PERTAMINA_PORTAL_URL || 'https://subsiditepat.mypertamina.id/LPG';

// Helper sleep berbasis Node.js agar aman dari Execution Context Destroyed saat page reload
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));



// =========================================================================
// SISTEM PEMACAH CAPTCHA IN-HOUSE (COMPUTER VISION / CANVAS PIXEL MATCH)
// =========================================================================
async function findCaptchaFrame(page) {
    const checkFrameForCaptcha = async (frameContext, isMainPage) => {
        return await frameContext.evaluate((isMain) => {
            const isVisible = (el) => {
                const rect = el.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0 && window.getComputedStyle(el).display !== 'none' && window.getComputedStyle(el).visibility !== 'hidden';
            };

            // 1. Cari indikator kuat bahwa ini adalah captcha
            const txtBody = document.body ? document.body.textContent : '';
            const hasCaptchaText = txtBody.includes('Cocokan Gambar') ||
                txtBody.includes('GESER tombol') ||
                txtBody.includes('Geser ke kanan') ||
                txtBody.includes('proses keamanan');
            const hasCaptchaClass = !!document.querySelector('[class*="geetest"]') ||
                !!document.querySelector('[class*="captcha"]') ||
                !!document.querySelector('[class*="secsdk"]') ||
                !!document.querySelector('[class*="verify"]');

            // Jika di main page, kita WAJIB memiliki kelas captcha atau teks captcha spesifik untuk menghindari false positive
            if (isMain && !hasCaptchaClass && !hasCaptchaText) {
                return false;
            }

            // 2. Temukan container captcha aktif
            let container = null;
            const divs = Array.from(document.querySelectorAll('div, section, form, [class*="modal"], [class*="dialog"], [class*="captcha"]')).filter(isVisible);
            for (const div of divs) {
                const txt = (div.textContent || '').toLowerCase();
                const matchTxt = txt.includes('cocokan gambar') || txt.includes('geser tombol') || txt.includes('geser ke kanan') || txt.includes('proses keamanan');
                if (matchTxt) {
                    const hasImg = div.querySelector('img, canvas');
                    const rect = div.getBoundingClientRect();
                    if (hasImg && rect.width <= 800 && rect.height <= 800) {
                        container = div;
                        break;
                    }
                }
            }

            if (!container && (hasCaptchaClass || hasCaptchaText)) {
                container = document.body;
            }

            if (!container) return false;

            // 3. Cari tombol geser/slider button
            const commonButtonSelectors = [
                '[class*="slider__button"]', '[class*="slider-button"]', '[class*="slider_btn"]',
                '[class*="slider-btn"]', '[class*="slider_handler"]', '[class*="slider-handle"]',
                '[class*="geetest_slider_button"]', '.ui-slider-handle', '.nc_scale_btn', '.handler',
                '.geetest_btn', '.secsdk-captcha-drag-icon', '.verify-btn', '.slider-btn'
            ];
            let hasSliderBtn = false;
            for (let selector of commonButtonSelectors) {
                const btn = container.querySelector(selector);
                if (btn && isVisible(btn)) {
                    hasSliderBtn = true;
                    break;
                }
            }

            if (!hasSliderBtn) {
                const elements = Array.from(container.querySelectorAll('*')).filter(isVisible);
                const hasDragText = elements.some(e => {
                    const txt = (e.textContent || '').trim().toLowerCase();
                    return txt.includes('geser') || txt.includes('slide') || txt.includes('drag');
                });
                if (hasDragText) {
                    hasSliderBtn = true;
                }
            }

            // 4. Cari background captcha (canvas atau img ukuran sedang)
            const imgs = Array.from(container.querySelectorAll('img, canvas')).filter(isVisible);
            const hasBgImg = imgs.some(el => {
                const r = el.getBoundingClientRect();
                return r.width >= 120 && r.width <= 500 && r.height >= 50 && r.height <= 300;
            });

            return hasSliderBtn && hasBgImg;
        }, isMainPage).catch(() => false);
    };

    try {
        // Cek child frames terlebih dahulu (karena captcha MyPertamina selalu di dalam iframe)
        for (const frame of page.frames()) {
            if (frame === page) continue;
            if (await checkFrameForCaptcha(frame, false)) {
                return frame;
            }
        }

        // Cek main page sebagai fallback terakhir
        if (await checkFrameForCaptcha(page, true)) {
            return page;
        }
    } catch (err) {
        console.log('[Captcha Detector] Error saat mencari frame:', err.message);
    }
    return null;
}

let lastBgSrc = '';

async function autoSolveSliderCaptcha(page) {
    try {
        console.log('[In-House Solver] Mengaktifkan Computer Vision untuk menganalisa Captcha...');

        // 1. Temukan frame yang berisi captcha
        const captchaFrame = await findCaptchaFrame(page);
        if (!captchaFrame) {
            console.log('[In-House Solver] Gagal: Frame captcha tidak ditemukan.');
            return false;
        }

        // --- TUNGGU HINGGA CAPTCHA BARU BENAR-BENAR SIAP ---
        console.log('[In-House Solver] Menunggu animasi transisi UI captcha (2.0 detik)...');
        await page.waitForTimeout ? await page.waitForTimeout(2000) : await new Promise(r => setTimeout(r, 2000)); // Dipercepat agar captcha tidak keburu auto-refresh karena inactivity

        const isReady = await captchaFrame.evaluate(() => {
            const isVisible = (el) => {
                const rect = el.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0 && window.getComputedStyle(el).display !== 'none' && window.getComputedStyle(el).visibility !== 'hidden';
            };
            const allImgs = Array.from(document.querySelectorAll('img, canvas')).filter(isVisible);
            return { ready: allImgs.length >= 2, bgSrc: 'bypass' };
        });

        if (!isReady.ready) {
            console.log('[In-House Solver] Peringatan: Gambar captcha baru belum siap atau loading lambat. Melanjutkan dengan elemen yang ada...');
        } else {
            console.log('[In-House Solver] Gambar captcha baru terverifikasi steril dan siap.');
            lastBgSrc = isReady.bgSrc;
        }

        // 2. Dapatkan offset koordinat jika captcha di dalam iframe
        let frameOffset = { x: 0, y: 0 };
        if (captchaFrame !== page) {
            try {
                const iframeElement = await captchaFrame.frameElement();
                if (iframeElement) {
                    const box = await iframeElement.boundingBox();
                    if (box) {
                        frameOffset = { x: box.x, y: box.y };
                        console.log(`[In-House Solver] Frame Captcha berupa iframe terdeteksi pada offset: x=${Math.round(frameOffset.x)}, y=${Math.round(frameOffset.y)}`);
                    }
                }
            } catch (iframeErr) {
                console.log('[In-House Solver] Warning: Gagal mendapatkan frameElement, mencoba alternatif...');
            }

            // Fallback offset jika masih 0,0: Cari iframe yang sesuai di parent frame
            if (frameOffset.x === 0 && frameOffset.y === 0) {
                const iframeOffsetAlt = await page.evaluate((url) => {
                    const iframes = Array.from(document.querySelectorAll('iframe'));
                    const target = iframes.find(f => f.src === url || (f.contentWindow && f.contentWindow.location.href === url));
                    if (target) {
                        const rect = target.getBoundingClientRect();
                        return { x: rect.left, y: rect.top };
                    }
                    const targetPart = iframes.find(f => f.src && f.src.includes(url.split('?')[0]));
                    if (targetPart) {
                        const rect = targetPart.getBoundingClientRect();
                        return { x: rect.left, y: rect.top };
                    }
                    const largestIframe = iframes.sort((a, b) => b.getBoundingClientRect().width - a.getBoundingClientRect().width)[0];
                    if (largestIframe) {
                        const rect = largestIframe.getBoundingClientRect();
                        return { x: rect.left, y: rect.top };
                    }
                    return null;
                }, captchaFrame.url()).catch(() => null);

                if (iframeOffsetAlt) {
                    frameOffset = iframeOffsetAlt;
                    console.log(`[In-House Solver] Offset iframe alternatif ditemukan: x=${Math.round(frameOffset.x)}, y=${Math.round(frameOffset.y)}`);
                }
            }
        }
        // 3. Dapatkan posisi tombol geser/handler dan track
        // Cek dulu di dalam captchaFrame, lalu di page utama (jika di luar frame)
        let btnRect = null;
        let isBtnInFrame = false;

        const findBtnLogic = () => {
            const elements = Array.from(document.querySelectorAll('*'));
            const textNode = elements.find(e => e.textContent && e.textContent.trim().toLowerCase() === 'geser ke kanan');

            if (textNode) {
                let track = textNode.parentElement;
                while (track && track.getBoundingClientRect().width < 150) track = track.parentElement;
                if (track) {
                    const children = Array.from(track.querySelectorAll('*'));
                    let leftmostBtn = null;
                    let minX = Infinity;
                    for (let child of children) {
                        const rect = child.getBoundingClientRect();
                        if (rect.width >= 30 && rect.width <= 80 && rect.height >= 30 && rect.height <= 80) {
                            if (rect.x < minX) {
                                minX = rect.x;
                                leftmostBtn = rect;
                            }
                        }
                    }
                    const tRect = track.getBoundingClientRect();
                    if (leftmostBtn) return { x: leftmostBtn.x, y: leftmostBtn.y, width: leftmostBtn.width, height: leftmostBtn.height, trackWidth: tRect.width };
                    return { x: tRect.x, y: tRect.y, width: 50, height: tRect.height, trackWidth: tRect.width };
                }
            }

            const selectors = ['.slider__button', '.slider-button', '.geetest_slider_button', '.secsdk-captcha-drag-icon', '.verify-btn', '.slider-btn', '.ui-slider-handle'];
            for (let sel of selectors) {
                const btn = document.querySelector(sel);
                if (btn) {
                    const rect = btn.getBoundingClientRect();
                    if (rect.width > 0) {
                        let track = btn.parentElement;
                        while (track && track.getBoundingClientRect().width < 100) track = track.parentElement;
                        return { x: rect.left, y: rect.top, width: rect.width, height: rect.height, trackWidth: track ? track.getBoundingClientRect().width : rect.width + 200 };
                    }
                }
            }
            return null;
        };

        // Cek iframe dulu
        btnRect = await captchaFrame.evaluate(findBtnLogic).catch(() => null);
        if (btnRect) {
            isBtnInFrame = true;
        } else {
            // Kalau tidak ada di iframe, cek di page utama
            btnRect = await page.evaluate(findBtnLogic).catch(() => null);
        }

        if (!btnRect) {
            console.log('[In-House Solver] Gagal: Tombol geser/Track tidak ditemukan di layar utama maupun iframe.');
            return false;
        }

        if (isBtnInFrame) {
            btnRect.x += frameOffset.x;
            btnRect.y += frameOffset.y;
        }
        // 4. Dapatkan rect gambar background & posisi kepingan puzzle dari frame secara dinamis
        const captchaInfo = await captchaFrame.evaluate(() => {
            const isVisible = (el) => {
                const rect = el.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0 && window.getComputedStyle(el).display !== 'none' && window.getComputedStyle(el).visibility !== 'hidden';
            };

            // Temukan container captcha aktif
            let container = null;
            const divs = Array.from(document.querySelectorAll('div, section, form, [class*="modal"], [class*="dialog"], [class*="captcha"]')).filter(isVisible);
            for (const div of divs) {
                const txt = (div.textContent || '').toLowerCase();
                const matchTxt = txt.includes('cocokan gambar') || txt.includes('geser tombol') || txt.includes('geser ke kanan') || txt.includes('proses keamanan');
                if (matchTxt) {
                    const hasImg = div.querySelector('img, canvas');
                    const rect = div.getBoundingClientRect();
                    if (hasImg && rect.width <= 800 && rect.height <= 800) {
                        container = div;
                        break;
                    }
                }
            }

            if (!container) {
                container = document.body;
            }

            const allImgs = Array.from(container.querySelectorAll('img, canvas')).filter(isVisible);
            if (allImgs.length === 0) return null;

            // Cari background image (lebar biasanya 150px - 500px)
            const candidates = allImgs.filter(el => {
                const r = el.getBoundingClientRect();
                return r.width >= 150 && r.width <= 500 && r.height >= 80 && r.height <= 300;
            });

            const bgImg = candidates.sort((a, b) => b.getBoundingClientRect().width - a.getBoundingClientRect().width)[0] ||
                allImgs.sort((a, b) => b.getBoundingClientRect().width - a.getBoundingClientRect().width)[0];

            if (!bgImg) return null;
            const bgR = bgImg.getBoundingClientRect();

            // Cari kepingan puzzle (piece)
            let pieceEl = allImgs.find(el => {
                if (el === bgImg) return false;
                const r = el.getBoundingClientRect();
                const isPieceSize = r.width >= 20 && r.width <= 100 && r.height >= 20 && r.height <= 100;
                const overlapsVertically = r.top >= bgR.top - 15 && r.bottom <= bgR.bottom + 15;
                return isPieceSize && overlapsVertically;
            });

            if (!pieceEl) {
                pieceEl = allImgs.find(el => {
                    if (el === bgImg) return false;
                    const r = el.getBoundingClientRect();
                    return r.width >= 20 && r.width <= 100;
                });
            }

            let pieceScreenX = 10;
            let pieceScreenY = 10;
            let pieceScreenW = 50;
            let pieceScreenH = 50;

            if (pieceEl) {
                const pr = pieceEl.getBoundingClientRect();
                const rawX = pr.left - bgR.left;
                const rawY = pr.top - bgR.top;

                if (rawX >= -50 && rawX < bgR.width * 0.45) pieceScreenX = rawX;
                pieceScreenY = rawY >= 0 ? rawY : 0;
                pieceScreenW = pr.width;
                pieceScreenH = pr.height;

                // Jika kepingan puzzle merender secara "full height" (seperti irisan slice vertikal)
                // Kita harus mengekstrak dimensi aslinya secara dinamis memindai piksel transparansinya
                if (pieceScreenH > bgR.height * 0.7) {
                    try {
                        const tempC = document.createElement('canvas');
                        tempC.width = pr.width;
                        tempC.height = pr.height;
                        const ctx = tempC.getContext('2d');
                        if (pieceEl.tagName === 'CANVAS') {
                            ctx.drawImage(pieceEl, 0, 0);
                        } else {
                            ctx.drawImage(pieceEl, 0, 0, pr.width, pr.height);
                        }

                        const imgD = ctx.getImageData(0, 0, tempC.width, tempC.height).data;
                        let minY = tempC.height, maxY = 0;
                        let minX = tempC.width, maxX = 0;

                        for (let y = 0; y < tempC.height; y++) {
                            for (let x = 0; x < tempC.width; x++) {
                                const alpha = imgD[(y * tempC.width + x) * 4 + 3];
                                if (alpha > 20) {
                                    if (y < minY) minY = y;
                                    if (y > maxY) maxY = y;
                                    if (x < minX) minX = x;
                                    if (x > maxX) maxX = x;
                                }
                            }
                        }

                        if (minY <= maxY && minX <= maxX) {
                            pieceScreenY = rawY + minY;
                            pieceScreenH = maxY - minY + 1;

                            // Crop juga sumbu X agar kotak tidak kebesaran oleh transparansi!
                            pieceScreenX = Math.max(0, rawX + minX);
                            pieceScreenW = maxX - minX + 1;
                        } else {
                            pieceScreenY = rawY + Math.floor(pr.height / 2) - 25;
                            pieceScreenH = 50;
                            pieceScreenW = 50;
                        }
                    } catch (e) {
                        pieceScreenY = rawY + Math.floor(pr.height / 2) - 25;
                        pieceScreenH = 50;
                        pieceScreenW = 50;
                    }
                }
            }

            return {
                bgRect: { left: bgR.left, top: bgR.top, width: bgR.width, height: bgR.height },
                pieceScreenX,
                pieceScreenY,
                pieceScreenW,
                pieceScreenH,
                rawX: pieceEl ? (pieceEl.getBoundingClientRect().left - bgR.left) : 0
            };
        });

        if (!captchaInfo || !captchaInfo.bgRect) {
            console.log('[In-House Solver] Gagal: Gambar background captcha tidak ditemukan.');
            return false;
        }

        let { bgRect, pieceScreenX, pieceScreenY, pieceScreenW, pieceScreenH } = captchaInfo;

        // Hitung koordinat absolut background untuk screenshot
        const absoluteBgRect = {
            left: bgRect.left + frameOffset.x,
            top: bgRect.top + frameOffset.y,
            width: bgRect.width,
            height: bgRect.height
        };

        // 5. Menganalisa letak lubang — Metode Utama: FullBG Subtraction, Fallback: Combined Scoring
        console.log('[In-House Solver] Mengaktifkan Computer Vision untuk menganalisa Captcha...');

        const holeResult = await captchaFrame.evaluate(async (pW, pH, pY, pX) => {
            const allCanvases = Array.from(document.querySelectorAll('canvas'));
            const allImgs = Array.from(document.querySelectorAll('img, canvas'));

            const bgImg = allImgs.find(el => {
                const r = el.getBoundingClientRect();
                return r.width > 150 && r.height > 100;
            });
            if (!bgImg) return { holeCenter: 150 };

            const natW = bgImg.naturalWidth || bgImg.width;
            const natH = bgImg.naturalHeight || bgImg.height;
            const scaleRatio = bgImg.getBoundingClientRect().width / natW;

            const bgC = document.createElement('canvas');
            bgC.width = natW; bgC.height = natH;
            const bgCtx = bgC.getContext('2d');
            try { bgCtx.drawImage(bgImg, 0, 0, natW, natH); } catch(e) { return { holeCenter: 150 }; }
            const bgData = bgCtx.getImageData(0, 0, natW, natH).data;

            const naturalPW = Math.floor(pW / scaleRatio);

            // === METODE 1: FULLBG PIXEL SUBTRACTION ===
            // Cari elemen full-background (tanpa lubang) dari canvas DAN img
            let fullBgEl = null;
            const allMediaElements = [...allCanvases, ...Array.from(document.querySelectorAll('img'))];

            // Pass 1: Cari berdasarkan class name
            for (const c of allMediaElements) {
                const cn = (c.className || '').toLowerCase();
                if (cn.includes('fullbg') || cn.includes('full_bg') || cn.includes('complete')) { fullBgEl = c; break; }
            }
            // Pass 2: Cari canvas/img tersembunyi dengan dimensi sama (hidden full background)
            if (!fullBgEl) {
                for (const c of allMediaElements) {
                    if (c === bgImg) continue;
                    const elW = c.naturalWidth || c.width;
                    const elH = c.naturalHeight || c.height;
                    if (elW >= natW - 5 && elW <= natW + 5 && elH >= natH - 5) {
                        const st = window.getComputedStyle(c);
                        const isHidden = st.display === 'none' || st.visibility === 'hidden' || parseFloat(st.opacity) === 0 || c.getBoundingClientRect().width === 0;
                        if (isHidden) { fullBgEl = c; break; }
                    }
                }
            }
            // Pass 3: Cari elemen visible kedua dengan ukuran serupa (mungkin ditumpuk di belakang)
            if (!fullBgEl) {
                for (const c of allMediaElements) {
                    if (c === bgImg) continue;
                    const elW = c.naturalWidth || c.width;
                    const elH = c.naturalHeight || c.height;
                    if (elW > 200 && elH > 100 && Math.abs(elW - natW) <= 10) { fullBgEl = c; break; }
                }
            }

            if (fullBgEl) {
                const fbC = document.createElement('canvas');
                fbC.width = natW; fbC.height = natH;
                const fbCtx = fbC.getContext('2d');
                let fbOk = true;
                try { fbCtx.drawImage(fullBgEl, 0, 0, natW, natH); } catch(e) { fbOk = false; }

                if (fbOk) {
                    const fbData = fbCtx.getImageData(0, 0, natW, natH).data;

                    // Step 1: Hitung column-wise luminance difference
                    const colDiff = new Float32Array(natW);
                    for (let x = 0; x < natW; x++) {
                        let ds = 0;
                        for (let y = 0; y < natH; y++) {
                            const i = (y * natW + x) * 4;
                            const lBg = 0.299*bgData[i]+0.587*bgData[i+1]+0.114*bgData[i+2];
                            const lFb = 0.299*fbData[i]+0.587*fbData[i+1]+0.114*fbData[i+2];
                            ds += Math.abs(lBg - lFb);
                        }
                        colDiff[x] = ds;
                    }

                    // Step 2: Tentukan threshold dinamis (30% dari peak)
                    let peakDiff = 0;
                    for (let x = 0; x < natW; x++) {
                        if (colDiff[x] > peakDiff) peakDiff = colDiff[x];
                    }
                    const diffThreshold = peakDiff * 0.3;

                    // Step 3: Temukan cluster kolom yang melebihi threshold
                    let clusters = [];
                    let curCluster = null;
                    for (let x = 0; x < natW; x++) {
                        if (colDiff[x] > diffThreshold) {
                            if (!curCluster) curCluster = { start: x, end: x, weight: 0 };
                            curCluster.end = x;
                            curCluster.weight += colDiff[x];
                        } else {
                            if (curCluster) { clusters.push(curCluster); curCluster = null; }
                        }
                    }
                    if (curCluster) clusters.push(curCluster);

                    // Gabungkan cluster yang berdekatan (gap <= 5px, akibat noise)
                    let merged = [];
                    for (const c of clusters) {
                        if (merged.length > 0 && c.start - merged[merged.length-1].end <= 5) {
                            merged[merged.length-1].end = c.end;
                            merged[merged.length-1].weight += c.weight;
                        } else {
                            merged.push({...c});
                        }
                    }

                    // Step 4: Pilih cluster terbesar (berdasarkan total weight)
                    const bestCluster = merged.sort((a, b) => b.weight - a.weight)[0];

                    if (bestCluster && (bestCluster.end - bestCluster.start) >= naturalPW * 0.5) {
                        // Step 5: Hitung centroid (pusat massa) dari cluster terbaik
                        let weightedSum = 0, totalWeight = 0;
                        for (let x = bestCluster.start; x <= bestCluster.end; x++) {
                            weightedSum += x * colDiff[x];
                            totalWeight += colDiff[x];
                        }
                        const centroidX = totalWeight > 0 ? weightedSum / totalWeight : (bestCluster.start + bestCluster.end) / 2;
                        const detectedW = bestCluster.end - bestCluster.start + 1;

                        return {
                            holeCenter: centroidX * scaleRatio,
                            holeWidth: detectedW * scaleRatio,
                            holeLeftX: bestCluster.start,
                            method: 'fullbg-centroid', score: bestCluster.weight, scaleRatio
                        };
                    }

                    // Fallback: sliding window jika clustering gagal
                    let maxD = 0, bestX = 0;
                    for (let x = 0; x < natW - naturalPW; x++) {
                        let rd = 0;
                        for (let dx = 0; dx < naturalPW; dx++) rd += colDiff[x + dx];
                        if (rd > maxD) { maxD = rd; bestX = x; }
                    }
                    return {
                        holeCenter: (bestX + naturalPW / 2) * scaleRatio,
                        holeWidth: naturalPW * scaleRatio,
                        holeLeftX: bestX, method: 'fullbg-sliding-window', score: maxD, scaleRatio
                    };
                }
            }

            // === METODE 2 (FALLBACK): Contour & Contrast Scoring ===
            const naturalPY = Math.floor(pY / scaleRatio);
            const naturalPH = Math.floor(pH / scaleRatio);
            const naturalPX = Math.floor(pX / scaleRatio);
            // Mulai scan setelah area kepingan asli
            const startScanX = Math.max(Math.floor(natW * 0.35), naturalPX + naturalPW + 5);
            const startY = Math.max(0, naturalPY);
            const endY = Math.min(natH, naturalPY + naturalPH);
            const bH = endY - startY;
            if (bH <= 0) return { holeCenter: 150 };

            // 1. Hitung rata-rata kecerahan per kolom (untuk deteksi area terang/gelap)
            const colAvg = new Float32Array(natW);
            for (let x = 0; x < natW; x++) {
                let s = 0;
                for (let y = startY; y < endY; y++) {
                    const i = (y * natW + x) * 4;
                    s += 0.299*bgData[i] + 0.587*bgData[i+1] + 0.114*bgData[i+2];
                }
                colAvg[x] = s / bH;
            }

            // 2. Hitung kekuatan edge vertikal per kolom (untuk deteksi batas kepingan)
            const edgeStr = new Float32Array(natW);
            for (let x = 1; x < natW - 1; x++) {
                let es = 0;
                for (let y = startY; y < endY; y++) {
                    const iP = (y * natW + (x - 1)) * 4;
                    const iN = (y * natW + (x + 1)) * 4;
                    es += Math.abs(
                        (0.299*bgData[iP] + 0.587*bgData[iP+1] + 0.114*bgData[iP+2]) -
                        (0.299*bgData[iN] + 0.587*bgData[iN+1] + 0.114*bgData[iN+2])
                    );
                }
                edgeStr[x] = es / bH;
            }

            // 3. Scoring setiap posisi X yang mungkin
            let maxSc = -Infinity, bestX2 = startScanX;
            for (let x = startScanX; x < natW - naturalPW - 5; x++) {
                // Rata-rata kecerahan di dalam area lubang
                let innerSum = 0;
                for (let dx = 0; dx < naturalPW; dx++) innerSum += colAvg[x + dx];
                const innerAvg = innerSum / naturalPW;

                // Rata-rata kecerahan di luar area lubang (border)
                let borderSum = 0, borderCount = 0;
                for (let dx = 1; dx <= 10; dx++) {
                    if (x - dx >= 0) { borderSum += colAvg[x - dx]; borderCount++; }
                    if (x + naturalPW + dx < natW) { borderSum += colAvg[x + naturalPW + dx]; borderCount++; }
                }
                const borderAvg = borderCount > 0 ? borderSum / borderCount : innerAvg;
                
                // Contrast bisa positif (overlay putih/terang) atau negatif (overlay gelap/hitam)
                // Kita gunakan absolute difference karena MyPertamina sering pakai overlay putih.
                const contrast = Math.abs(borderAvg - innerAvg);

                // Kekuatan edge di batas kiri dan kanan lubang (dengan margin error ±2px)
                let leftEdge = 0, rightEdge = 0;
                for (let d = -2; d <= 2; d++) {
                    leftEdge += edgeStr[Math.max(0, x + d)] || 0;
                    rightEdge += edgeStr[Math.min(natW - 1, x + naturalPW + d)] || 0;
                }
                
                // Skor akhir = Kombinasi contrast area dengan kekuatan edge di batasnya
                // Kita kembali menggunakan penjumlahan (+) karena ini sudah terbukti akurat sebelumnya
                const score = contrast * (leftEdge + rightEdge);
                
                if (score > maxSc) { 
                    maxSc = score; 
                    bestX2 = x; 
                }
            }
            
            return {
                holeCenter: (bestX2 + naturalPW / 2) * scaleRatio,
                holeWidth: naturalPW * scaleRatio,
                holeLeftX: bestX2, method: 'combined-scoring', score: maxSc, scaleRatio
            };
        }, pieceScreenW, pieceScreenH, pieceScreenY, pieceScreenX);

        const holeCenter = holeResult.holeCenter;

        // 6. Kalkulasi Gerakan (Mapping 1:1)
        const maxSliderTravel = btnRect.trackWidth - btnRect.width;
        
        // Kita tidak memakai ratio karena berdasarkan pengujian, gerakan layar mouse (pixel)
        // ter-map secara 1:1 dengan pergerakan elemen kepingan di CSS MyPertamina.
        
        const pieceCenter = pieceScreenX + (pieceScreenW / 2);
        const pieceDragRequired = holeCenter - pieceCenter;

        // Jarak murni yang dibutuhkan kepingan.
        // Berdasarkan pengamatan visual user, gerakan 1:1 murni menyebabkan undershoot sekitar 10%.
        // Kita akan menambahkan dorongan ekstra 10% (multiplier 1.10) agar pas.
        const trackScale = 1.10; 
        let targetDistance = pieceDragRequired * trackScale;

        // DEBUG LOGGING UNTUK INVESTIGASI DETAIL
        console.log(`[In-House Solver Debug] === DETAIL KALKULASI CAPTCHA ===`);
        console.log(`[In-House Solver Debug] bgRect: width=${bgRect.width}`);
        console.log(`[In-House Solver Debug] btnRect: trackWidth=${btnRect.trackWidth}, width=${btnRect.width}, maxSliderTravel=${Math.round(maxSliderTravel)}`);
        console.log(`[In-House Solver Debug] pieceCenter: ${Math.round(pieceCenter)}, holeCenter: ${Math.round(holeCenter)}`);
        console.log(`[In-House Solver Debug] pieceDragRequired: ${Math.round(pieceDragRequired)} (1:1 Movement)`);
        console.log(`[In-House Solver Debug] targetDistance (Drag Mouse): ${Math.round(targetDistance)}px`);
        console.log(`[In-House Solver Debug] ===================================`);

        // Visualisasi debug di dalam frame — menggunakan lebar lubang TERDETEKSI, bukan lebar kepingan
        const detectedHoleWidth = holeResult.holeWidth || pieceScreenW;
        await captchaFrame.evaluate((bLeft, bTop, hC, hW, pH, pY) => {
            const old = document.getElementById('rpa-debug-line');
            if (old) old.remove();
            const d = document.createElement('div');
            d.id = 'rpa-debug-line';
            d.style.cssText = `position:fixed;left:${bLeft + hC - (hW / 2)}px;top:${bTop + pY}px;height:${pH}px;width:${hW}px;border:2px dashed red;background:rgba(255,0,0,0.15);z-index:99999;pointer-events:none;`;
            document.body.appendChild(d);

            const centerLine = document.createElement('div');
            centerLine.style.cssText = `position:absolute;left:${hW / 2}px;top:0;height:100%;width:2px;background:red;`;
            d.appendChild(centerLine);
        }, bgRect.left, bgRect.top, holeCenter, detectedHoleWidth, pieceScreenH, pieceScreenY);

        // Validasi jarak: jika terlalu kecil, kemungkinan deteksi salah — skip dan refresh
        if (!targetDistance || targetDistance < 15) {
            console.log(`[In-House Solver] ⚠️ Jarak terdeteksi terlalu kecil (${Math.round(targetDistance || 0)}px). Deteksi mungkin salah, melewati percobaan ini.`);
            return false;
        }

        console.log(`[In-House Solver] Target lubang terdeteksi. Menggeser slider sejauh: ${Math.round(targetDistance)}px. (maxTravel=${Math.round(maxSliderTravel)}px)`);

        // 7. Simulasi geseran mouse manusia (Anti-Deteksi dengan Reset Jumper)
        const startX = btnRect.x + (btnRect.width / 2);
        const startY = btnRect.y + (btnRect.height / 2);

        console.log(`[In-House Solver] Memulai tarikan dari Kordinat X: ${Math.round(startX)}, Y: ${Math.round(startY)}`);

        // Reset pergerakan mouse menjauh dari area captcha untuk menghilangkan jejak tarikan sebelumnya
        const safeX = Math.floor(Math.random() * 200 + 50);
        const safeY = Math.floor(Math.random() * 200 + 50);
        await page.mouse.move(safeX, safeY, { steps: 5 });
        await sleep(400);

        // Dekati tombol secara perlahan
        await page.mouse.move(startX, startY, { steps: 10 });
        await sleep(300);
        await page.mouse.down();
        await sleep(200);

        // Gerakkan kursor meniru manusia (Ease-in-out Bezier curve + micro-pauses)
        // Smoothstep: percepatan awal → kecepatan puncak di tengah → perlambatan di akhir
        const smoothstep = (t) => t * t * (3 - 2 * t);
        const steps = 25 + Math.floor(Math.random() * 15); // 25-40 steps (variabel)
        let currentX = startX;
        for (let i = 1; i <= steps; i++) {
            const t = i / steps;
            const eased = smoothstep(t); // Non-linear progression
            currentX = startX + (targetDistance * eased);

            // Y wobble: sedikit naik di awal, turun di akhir (pola busur alami)
            const arcY = Math.sin(t * Math.PI) * (1.5 + Math.random() * 1.5);
            const jitterY = (Math.random() - 0.5) * 1.2;
            let wobbleY = startY - arcY + jitterY;

            await page.mouse.move(currentX, wobbleY, { steps: 1 });

            // Jeda variabel: lebih lambat di awal dan akhir, cepat di tengah
            const baseDelay = t < 0.2 || t > 0.85 ? 25 : 10;
            await sleep(baseDelay + Math.random() * 12);

            // Micro-pause acak (1 dari 8 kesempatan) meniru ragu-ragu manusia
            if (Math.random() < 0.125 && t > 0.3 && t < 0.8) {
                await sleep(40 + Math.random() * 60);
            }
        }

        // Overshoot proporsional terhadap jarak (lebih jauh = lebih kebablasan)
        const overshootPx = 2 + Math.random() * 3 + targetDistance * 0.015;
        await page.mouse.move(currentX + overshootPx, startY + (Math.random() * 1.5 - 0.5), { steps: 4 });
        await sleep(80 + Math.random() * 100);

        // Koreksi mundur perlahan ke titik pas
        await page.mouse.move(startX + targetDistance, startY + (Math.random() * 0.8 - 0.4), { steps: 5 });
        await sleep(60 + Math.random() * 80);

        // Lepaskan
        await page.mouse.up();

        console.log('[In-House Solver] Tarikan dilepas. Menunggu validasi...');
        await sleep(2500);

        // 8. Cek keberhasilan di dalam frame menggunakan pencarian ulang frame yang steril
        let isStillThere = false;
        try {
            const currentCaptchaFrame = await findCaptchaFrame(page);
            isStillThere = (currentCaptchaFrame !== null);
        } catch (frameErr) {
            console.log('[In-House Solver] Frame terlepas atau hancur, berasumsi captcha selesai.');
            isStillThere = false;
        }

        if (!isStillThere) {
            console.log('[In-House Solver] ✅ SUKSES! Captcha berhasil dipecahkan.');
            return true;
        } else {
            console.log('[In-House Solver] ❌ GAGAL. Mencoba refresh gambar captcha baru...');
            await captchaFrame.evaluate(() => {
                const refreshBtnSelectors = [
                    '[class*="refresh"]',
                    '[class*="change"]',
                    '[class*="ganti"]',
                    '.geetest_refresh_1'
                ];
                for (let sel of refreshBtnSelectors) {
                    const el = document.querySelector(sel);
                    if (el) { el.click(); return; }
                }
                const allElements = document.querySelectorAll('*');
                for (let el of allElements) {
                    const txt = (el.textContent || '').toLowerCase();
                    if (txt.includes('ganti') || txt.includes('refresh') || txt.includes('ubah')) {
                        el.click();
                        return;
                    }
                }
            }).catch(() => { });
            await sleep(2000);
            return false;
        }

    } catch (e) {
        console.log('[In-House Solver] Error saat memecahkan captcha: ', e.message);
        return false;
    }
}
// =========================================================================


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

async function startDaemon() {
    console.log('===================================================');
    console.log('     PM2 QUEUE WORKER RPA LGP PERTAMINA AKTIF      ');
    console.log('===================================================');
    console.log(`Menghubungkan ke Laravel: ${LARAVEL_URL}`);
    console.log('Pekerja (Worker) sedang berjalan di latar belakang (Daemon Mode)...');
    
    while (true) {
        try {
            const hasJob = await run();
            if (!hasJob) {
                // Sleep 5 seconds before polling again
                await sleep(5000);
            } else {
                // Sleep 2 seconds before grabbing next job to prevent instant loop spin
                await sleep(2000);
            }
        } catch (err) {
            console.error('✗ Kesalahan kritis pada Daemon Worker:', err.message);
            await sleep(5000);
        }
    }
}

async function run() {
    try {
        // Parse arguments (tetap simpan untuk jaga-jaga kalau mau run manual)
        const args = process.argv.slice(2);
        let targetSyncId = null;
        for (const arg of args) {
            if (arg.startsWith('--sync-id=')) {
                targetSyncId = arg.split('=')[1];
            }
        }

        // 1. Memeriksa antrean sinkronisasi
        const syncUrl = targetSyncId ? `/api/rpa-sync/pending?sync_id=${targetSyncId}` : '/api/rpa-sync/pending';
        
        let syncJob = null;
        let syncResponse = null;

        // Lakukan polling beberapa kali khusus untuk mengatasi delay commit database (Race Condition lambat)
        for (let attempt = 1; attempt <= 5; attempt++) {
            syncResponse = await api.get(syncUrl);
            syncJob = syncResponse.data.sync;
            
            if (syncJob) break;
            
            // Jika dalam mode run-once, tunggu dan coba lagi (maks 5 detik)
            if (process.argv.includes('--run-once')) {
                console.log(`[Menunggu Antrean...] Percobaan ${attempt}/5: Data belum masuk ke database, mencari lagi...`);
                await sleep(1500);
            } else {
                break; // Jika daemon, keluar dari loop karena daemon punya loop sendiri
            }
        }

        if (!syncJob) {
            if (process.argv.includes('--run-once')) {
                console.log('Tidak ada antrean ditemukan setelah menunggu. Mungkin sudah diambil oleh proses lain (Daemon) atau koneksi database sangat lambat.');
            }
            return false;
        }

        const syncId = syncJob.id;
        activeSyncId = syncId; // Set ke global untuk cleanup jika Ctrl+C
        console.log(`\n===================================================`);
        console.log(`Menemukan Job ID #${syncId} (Dibuat pada: ${syncJob.created_at})`);

        // 2. Ubah status job ke 'processing'
        // (Status 'processing' sekarang diatur langsung oleh Laravel saat endpoint /pending dipanggil)
        console.log(`Memproses Antrean untuk Agen (User ID): ${syncJob.user_id}`);

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
            return true;
        }

        console.log(`Ditemukan ${pangkalanList.length} akun pangkalan.`);

        let successCount = 0;
        let limitCount = 0;
        let failCount = 0;
        let completedSuccessfully = false;
        let anyPangkalanProcessed = false;
        let pangkalanRetries = {}; // Untuk melacak jumlah retry per pangkalan
        let pangkalanHabisList = [];
        let pangkalanSisaList = [];

        // Loop untuk login berantai ke masing-masing pangkalan
        for (let pIdx = 0; pIdx < pangkalanList.length; pIdx++) {
            const pangkalan = pangkalanList[pIdx];
            const isLastPangkalan = (pIdx === pangkalanList.length - 1);

            console.log(`\n---------------------------------------------------`);
            console.log(`[Akun ${pIdx + 1}/${pangkalanList.length}] Login ke: ${pangkalan.nama_pangkalan}`);
            console.log(`---------------------------------------------------`);
            
            // Update pangkalan target di database agar bisa dimonitor frontend
            await api.patch(`/api/rpa-sync/${syncId}`, { current_pangkalan: pangkalan.nama_pangkalan }).catch(() => {});

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

            // 4. Buka Browser (Headless: false agar terlihat layarnya)

            console.log('Membuka browser otomatis (Mode Tampil/UI)...');
            const browser = await puppeteer.launch({
                headless: true, // Berjalan di background (tanpa UI Chrome)
                defaultViewport: null, // Biarkan viewport menyesuaikan ukuran jendela secara otomatis
                args: ['--start-maximized', '--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu']
            });
            activeBrowser = browser; // Set ke global untuk cleanup jika Ctrl+C

            const [page] = await browser.pages();
            await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            console.log(`Membuka portal Pertamina: ${PERTAMINA_URL}`);
            await page.goto(PERTAMINA_URL, { waitUntil: 'domcontentloaded' });
            await sleep(2000);

            // Cek apakah halaman sedang maintenance
            const isMaintenance = await page.evaluate(() => {
                const text = document.body.textContent.toLowerCase();
                return text.includes('peningkatan layanan') || text.includes('maintenance') || text.includes('under construction');
            });

            if (isMaintenance) {
                pangkalanRetries[pangkalan.id] = (pangkalanRetries[pangkalan.id] || 0) + 1;
                console.log('🔴 Sistem MyPertamina sedang MAINTENANCE (Peningkatan Layanan).');
                
                if (pangkalanRetries[pangkalan.id] <= 10) {
                    console.log(`🔄 Halaman Maintenance/Overload. Tunggu 10 detik lalu refresh otomatis (Percobaan ke-${pangkalanRetries[pangkalan.id]})...`);
                    
                    // Update info ke frontend agar user tidak bingung
                    await api.patch(`/api/rpa-sync/${syncId}`, { 
                        current_pangkalan: `${pangkalan.nama_pangkalan} (Memantau Server Maintenance ${pangkalanRetries[pangkalan.id]}/10)` 
                    }).catch(() => {});

                    await sleep(10000); // Kasih jeda napas 10 detik buat servernya
                    await browser.close();
                    activeBrowser = null;
                    pIdx--; // Mundur 1 langkah agar login pangkalan ini diulang
                    continue;
                } else {
                    console.log(`❌ Menyerah karena web Pertamina terus-menerus maintenance setelah 10 kali percobaan.`);
                    // Tandai transaksi sebagai gagal di database
                    for (const trx of transactions) {
                        try {
                            await api.post(`/api/gagal-lapor/${trx.id}`, {
                                message: `Gagal: Sistem MyPertamina sedang Maintenance (Peningkatan Layanan) / Overload.`
                            });
                            failCount++;
                        } catch (e) {}
                    }
                    await browser.close();
                    activeBrowser = null;
                    continue;
                }
            }

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
            let loginSolverTries = 0;
            let loginWaitSeconds = 0;
            
            while (!isLoggedIn && loginWaitSeconds < 60) {
                try {
                    const currentUrl = page.url();
                    if (currentUrl.includes('/merchant/app') || currentUrl.includes('/merchant/dashboard') || currentUrl.includes('/penjualan')) {
                        isLoggedIn = true;
                    }
                } catch (e) { }

                if (!isLoggedIn) {
                    // Periksa apakah ada slider captcha di halaman login
                    try {
                        const captchaFrame = await findCaptchaFrame(page);
                        const hasCaptcha = (captchaFrame !== null);

                        if (hasCaptcha) {
                            if (loginSolverTries < 9999) {
                                loginSolverTries++;
                                console.log(`[Login] Mendeteksi Captcha Keamanan di halaman login. Mencoba meretas otomatis (Percobaan ke-${loginSolverTries})...`);
                                const solveSuccess = await autoSolveSliderCaptcha(page);
                                if (!solveSuccess && loginSolverTries >= 9999) {
                                    console.log('[Login] Bot menyerah setelah terlalu banyak gagal login captcha. Menunggu Anda menyelesaikannya secara manual...');
                                }
                                await sleep(2000);
                            }
                        }
                    } catch (captchaErr) {
                        console.error('[Login Captcha Check Error]:', captchaErr.message);
                    }

                    await sleep(1000);
                    loginWaitSeconds++;
                }
            }

            if (!isLoggedIn) {
                const finalUrl = page.url();
                console.log(`🔴 Gagal login setelah 60 detik. Posisi URL terakhir: ${finalUrl}`);
                
                // Ambil screenshot untuk keperluan debug
                try {
                    await page.screenshot({ path: 'error-timeout-login.png', fullPage: true });
                    console.log('📸 Screenshot halaman terakhir berhasil disimpan sebagai rpa-bot/error-timeout-login.png');
                } catch (err) {
                    console.log('Gagal mengambil screenshot:', err.message);
                }
                
                // Coba ambil pesan error merah di halaman
                const errorMsg = await page.evaluate(() => {
                    const errors = Array.from(document.querySelectorAll('.text-red-500, .text-danger, .error, [role="alert"]'));
                    for (const el of errors) {
                        if (el.textContent.trim().length > 0) return el.textContent.trim();
                    }
                    return null;
                });
                
                if (errorMsg) {
                    console.log(`[!] Kemungkinan penyebab gagal: "${errorMsg}"`);
                    throw new Error(`Gagal Login: ${errorMsg}`);
                } else if (finalUrl.includes('500') || finalUrl.includes('errors=')) {
                    throw new Error('Gagal Login: Server Pertamina Error 500 / Koneksi Terputus.');
                } else {
                    throw new Error('Gagal Login: Halaman Pertamina tidak merespons (Timeout 60s).');
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

            // Tunggu sebentar agar dashboard React merender data stok dengan sempurna
            await sleep(3000);

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
                if (!pangkalanHabisList.includes(merchantName)) pangkalanHabisList.push(merchantName);
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

            if (!pangkalanSisaList.includes(merchantName)) pangkalanSisaList.push(merchantName);

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
            } catch (e) { }

            // Menunggu halaman input NIK siap secara otomatis
            console.log('Menunggu Halaman Pencatatan Transaksi siap (mencari kolom NIK)...');
            let isInputPageReady = false;
            let waitingAttempts = 0;
            while (!isInputPageReady && waitingAttempts < 10) {
                try {
                    const isStokKosongPopup = await page.evaluate(() => {
                        const txt = document.body.textContent.toLowerCase();
                        return txt.includes('stok tabung kosong') || txt.includes('stok tabung yang dapat dijual kosong');
                    });
                    if (isStokKosongPopup) {
                        throw new Error('FATAL: STOK_MYPERTAMINA_KOSONG');
                    }

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

            if (!isInputPageReady) {
                pangkalanRetries[pangkalan.id] = (pangkalanRetries[pangkalan.id] || 0) + 1;
                console.log(`🔴 Gagal menemukan form NIK setelah 10 detik (Halaman mungkin Blank/Putih atau nge-Lag).`);
                
                if (pangkalanRetries[pangkalan.id] <= 2) {
                    console.log(`🔄 Melakukan restart browser untuk pangkalan ini (Percobaan ke-${pangkalanRetries[pangkalan.id]})...`);
                    await browser.close();
                    activeBrowser = null;
                    pIdx--; // Mundurkan index agar pangkalan ini diulang
                    continue;
                } else {
                    console.log(`❌ Gagal memuat halaman pangkalan setelah 2 kali percobaan. Melewati pangkalan ini...`);
                    // Tandai transaksi sebagai gagal di database
                    for (const trx of transactions) {
                        try {
                            await api.post(`/api/gagal-lapor/${trx.id}`, {
                                message: `Gagal: Halaman MyPertamina Blank Putih / Tidak memuat form penjualan setelah beberapa kali percobaan.`
                            });
                            failCount++;
                        } catch (e) {}
                    }
                    await browser.close();
                    activeBrowser = null;
                    continue;
                }
            }

            console.log('✓ Halaman pencatatan transaksi siap. Memulai input NIK...');
            await sleep(1000);

            let pangkalanStokHabis = false;

            // 6. Jalankan perulangan input transaksi
            let isAbortedByUser = false;
            for (let i = 0; i < transactions.length; i++) {
                const trx = transactions[i];

                // Cek apakah user menekan tombol "Reset RPA" di tengah jalan
                try {
                    const statusRes = await api.get(`/api/rpa-sync/${syncId}`);
                    if (statusRes.data && statusRes.data.sync && statusRes.data.sync.status === 'failed') {
                        console.log('🛑 [TUGAS DIBATALKAN] Anda menekan "Reset RPA". Bot otomatis dihentikan (Hard Reset)...');
                        isAbortedByUser = true;
                        break;
                    }
                } catch (e) {}

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
                        let retryOpenModal = 0;
                        let opened = false;
                        while (!opened && retryOpenModal < 3) {
                            retryOpenModal++;
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
                                    if (retryOpenModal === 1) {
                                        console.log('Membuka kembali modal "Catat Penjualan"...');
                                    } else {
                                        console.log(`🔄 [Self-Healing ${retryOpenModal-1}/2] Mencoba kembali menekan tombol "Catat Penjualan"...`);
                                    }
                                    
                                    const catatPenjualanBtn = await findButtonByKeywords(page, ['Catat Penjualan']);
                                    if (catatPenjualanBtn) {
                                        await catatPenjualanBtn.click();

                                        for (let w = 0; w < 20; w++) {
                                            const isStokKosongPopup = await page.evaluate(() => {
                                                const txt = document.body.textContent.toLowerCase();
                                                return txt.includes('stok tabung kosong') || txt.includes('stok tabung yang dapat dijual kosong');
                                            });
                                            if (isStokKosongPopup) {
                                                throw new Error('FATAL: STOK_MYPERTAMINA_KOSONG');
                                            }

                                            opened = await page.evaluate(() => {
                                                const inputs = Array.from(document.querySelectorAll('input'));
                                                return inputs.some(input => {
                                                    const rect = input.getBoundingClientRect();
                                                    return rect.width > 0 && rect.height > 0 && 
                                                        !input.readOnly && !input.disabled;
                                                });
                                            });
                                            if (opened) break;
                                            await sleep(500);
                                        }

                                        if (!opened && retryOpenModal < 3) {
                                            console.log(`⚠️ Kolom NIK lambat/tidak muncul (Percobaan ${retryOpenModal}/3). Melakukan refresh halaman...`);
                                            await page.reload({ waitUntil: 'networkidle2', timeout: 30000 }).catch(() => null);
                                            await sleep(3000);
                                            continue; // Coba loop sekali lagi
                                        }
                                    }
                                } else {
                                    opened = true;
                                }
                            } else {
                                opened = true; // Bukan di dashboard, mungkin di URL lain
                            }
                        }
                    } catch (e) {
                        if (e.message && e.message.includes('STOK_MYPERTAMINA_KOSONG')) {
                            throw e;
                        }
                    }

                    // Input NIK
                    console.log('Menginput NIK...');
                    let nikInput = null;
                    const NIK_SELECTOR = process.env.SELECTOR_NIK_INPUT;
                    if (NIK_SELECTOR && NIK_SELECTOR !== '#nik-input-id') {
                        try {
                            nikInput = await page.waitForSelector(NIK_SELECTOR, { timeout: 3000 });
                        } catch (e) { }
                    }
                    if (!nikInput) {
                        nikInput = await findInputByKeywords(page, ['nik', 'ktp', 'identity']);
                    }
                    if (!nikInput) {
                        nikInput = await page.$('input[type="text"]');
                    }

                    if (!nikInput) {
                        throw new Error('Gagal menemukan kotak isian nomor identitas di halaman web.');
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
                        } catch (e) { }
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
                                txt.includes('stok habis') ||
                                txt.includes('lakukan penebusan');
                            if (isStokKosong) {
                                return { state: 'stok_kosong' };
                            }

                            const isLimit = txt.includes('melebihi batas kewajaran') || txt.includes('batas kewajaran');
                            if (isLimit) {
                                return { state: 'limit' };
                            }

                            if (txt.includes('pernyataan persetujuan') || txt.includes('saya telah membaca')) {
                                return { state: 'pernyataan' };
                            }

                            if (txt.includes('data pelanggan belum lengkap') || txt.includes('lengkapi data dahulu')) {
                                return { state: 'incomplete_data' };
                            }

                            if (txt.includes('pilihan jenis pelanggan') || txt.includes('pelanggan terdaftar')) {
                                return { state: 'modal' };
                            }

                            const isLogin = txt.includes('nomor ponsel atau email') && txt.includes('merchant apps');
                            if (isLogin) {
                                return { state: 'login' };
                            }

                            const hasCekPesanan = txt.includes('cek pesanan') || txt.includes('jumlah produk');
                            if (hasCekPesanan) {
                                return { state: 'ready' };
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
                        await page.screenshot({ path: `debug-timeout-nik-${trx.nik}-${Date.now()}.png` });
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
                        if (!pangkalanHabisList.includes(merchantName)) pangkalanHabisList.push(merchantName);
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
                        limitCount++;
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

                    // --- HANDLE DYNAMIC PRE-STATES (Pernyataan & Incomplete Data) ---
                    let resolvingPreStates = true;
                    let resolveAttempts = 0;
                    while (resolvingPreStates && resolveAttempts < 10) {
                        resolveAttempts++;
                        
                        if (searchState === 'pernyataan') {
                            console.log('Mendeteksi Pernyataan Persetujuan. Mencentang kotak persetujuan...');
                            
                            // Centang semua checkbox
                            await page.evaluate(() => {
                                const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]'));
                                checkboxes.forEach(cb => {
                                    if (!cb.checked) cb.click();
                                });
                            });
                            await sleep(500);

                            const lanjutBtn = await findButtonByKeywords(page, ['SELANJUTNYA', 'LANJUTKAN']);
                            if (lanjutBtn) {
                                await lanjutBtn.click();
                                await sleep(2000);
                            }

                            // Re-evaluate state
                            let newState = null;
                            for (let attempt = 0; attempt < 15; attempt++) {
                                const pageData = await page.evaluate(() => {
                                    const txt = document.body.textContent.toLowerCase();
                                    if (txt.includes('data pelanggan belum lengkap') || txt.includes('lengkapi data dahulu')) {
                                        return { state: 'incomplete_data' };
                                    }
                                    if (txt.includes('berhasil diperbarui')) {
                                        return { state: 'success_modal' };
                                    }
                                    if (txt.includes('pilihan jenis pelanggan') || txt.includes('pelanggan terdaftar')) {
                                        return { state: 'modal' };
                                    }
                                    if (txt.includes('melebihi batas kewajaran') || txt.includes('batas kewajaran')) {
                                        return { state: 'limit' };
                                    }
                                    const isLogin = txt.includes('nomor ponsel atau email') && txt.includes('merchant apps');
                                    if (isLogin) return { state: 'login' };
                                    
                                    const hasCekPesanan = txt.includes('cek pesanan') || txt.includes('jumlah produk');
                                    if (hasCekPesanan) return { state: 'ready' };
                                    return { state: 'waiting' };
                                });
                                if (pageData.state !== 'waiting') {
                                    newState = pageData.state;
                                    break;
                                }
                                await sleep(500);
                            }
                            if (newState) {
                                searchState = newState;
                            } else {
                                throw new Error(`Status halaman tidak merespon setelah diklik (stuck di ${searchState}).`);
                            }
                            continue; // Re-evaluate loop
                        } 
                        
                        if (searchState === 'success_modal') {
                            console.log('Mendeteksi popup sukses update data...');
                            const lanjutTrxBtn = await findButtonByKeywords(page, ['LANJUTKAN KE TRANSAKSI']);
                            if (lanjutTrxBtn) {
                                await lanjutTrxBtn.click();
                                await sleep(2000);
                            }
                            
                            // Re-evaluate state
                            let newState = null;
                            for (let attempt = 0; attempt < 15; attempt++) {
                                const pageData = await page.evaluate(() => {
                                    const txt = document.body.textContent.toLowerCase();
                                    if (txt.includes('pernyataan persetujuan') || txt.includes('saya telah membaca')) {
                                        return { state: 'pernyataan' };
                                    }
                                    if (txt.includes('pilihan jenis pelanggan') || txt.includes('pelanggan terdaftar')) {
                                        return { state: 'modal' };
                                    }
                                    if (txt.includes('melebihi batas kewajaran') || txt.includes('batas kewajaran')) {
                                        return { state: 'limit' };
                                    }
                                    const isLogin = txt.includes('nomor ponsel atau email') && txt.includes('merchant apps');
                                    if (isLogin) return { state: 'login' };
                                    
                                    const hasCekPesanan = txt.includes('cek pesanan') || txt.includes('jumlah produk');
                                    if (hasCekPesanan) return { state: 'ready' };
                                    return { state: 'waiting' };
                                });
                                if (pageData.state !== 'waiting') {
                                    newState = pageData.state;
                                    break;
                                }
                                await sleep(500);
                            }
                            if (newState) {
                                searchState = newState;
                            } else {
                                throw new Error(`Status halaman tidak merespon setelah success_modal (stuck di ${searchState}).`);
                            }
                            continue;
                        }

                        if (searchState === 'incomplete_data') {
                            console.log('Mendeteksi Data Pelanggan Belum Lengkap. Membuka form Update Data...');
                            
                            const updateBtn = await findButtonByKeywords(page, ['UPDATE DATA PELANGGAN']);
                            if (updateBtn) {
                                await updateBtn.click();
                                await sleep(2000);
                                
                                // Cek apakah muncul modal "Segera Lengkapi NIB"
                                const isNibModal = await page.evaluate(() => {
                                    const txt = document.body.textContent.toLowerCase();
                                    return txt.includes('lengkapi nib') || txt.includes('nib sebelum') || txt.includes('segera lengkapi nib');
                                });
                                
                                if (isNibModal) {
                                    console.log('Mendeteksi modal kewajiban melengkapi NIB untuk Usaha Mikro.');
                                    throw new Error('Gagal: Pelanggan mikro wajib melengkapi NIB.');
                                }
                            }

                            console.log('Mengisi Tempat dan Tanggal Lahir...');
                            
                            // Tempat Lahir
                            let tempatLahirInput = await page.$('input[placeholder*="tempat lahir" i]');
                            if (!tempatLahirInput) {
                                tempatLahirInput = await findInputByKeywords(page, ['tempat lahir']);
                            }
                            if (tempatLahirInput) {
                                await tempatLahirInput.click({ clickCount: 3 });
                                await page.keyboard.press('Backspace');
                                await sleep(200);
                                // Prioritaskan tempat_lahir dari database
                                let tempatLahirVal = trx.tempat_lahir || trx.kabupaten_kota || '';
                                const cleanTempat = tempatLahirVal.replace(/^(KAB\.|KB\.|KABUPATEN|KOTA|KEC\.|KECAMATAN|DESA|KEL\.|KELURAHAN)\s*/i, '');
                                
                                // Gunakan Puppeteer type agar React/Mantine mendeteksi perubahan
                                if (cleanTempat) {
                                    await tempatLahirInput.type(cleanTempat, { delay: 30 });
                                } else {
                                    await tempatLahirInput.type('-', { delay: 30 });
                                }
                                await sleep(300);
                            }

                            // DUMP HTML UNTUK DEBUGGING DROPDOWN
                            const htmlDump = await page.evaluate(() => document.body.innerHTML);
                            const fs = require('fs');
                            fs.writeFileSync('update_data_form.html', htmlDump);
                            console.log('📄 HTML form Update Data berhasil di-dump ke update_data_form.html');

                            // Tanggal Lahir
                            let tglLahir = trx.tanggal_lahir;
                            
                            // Berikan tanggal default jika kosong di database karena field ini wajib
                            if (!tglLahir || tglLahir === '-') {
                                tglLahir = '1970-01-01';
                            }

                            if (tglLahir) { 
                                const [tahun, bulanStr, tanggal] = tglLahir.split('-');
                                const tglNum = parseInt(tanggal, 10).toString();
                                const bulanNama = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                const blnIdx = parseInt(bulanStr, 10) - 1;
                                const blnTxt = bulanNama[blnIdx];

                                // Mantine UI Custom Dropdown Interaction
                                const clickMantineSelect = async (placeholder, matchText) => {
                                    const inputs = await page.$$(`input[placeholder="${placeholder}"]`);
                                    if (inputs.length > 0) {
                                        // Gunakan Puppeteer click() asli agar React mendeteksi interaksi
                                        try {
                                            await inputs[0].click({ delay: 50 });
                                        } catch (err) {
                                            await page.evaluate(el => {
                                                el.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
                                                el.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                                                el.dispatchEvent(new PointerEvent('pointerup', { bubbles: true }));
                                                el.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
                                                el.click();
                                            }, inputs[0]);
                                        }
                                        await sleep(800); // wait for dropdown animation
                                        
                                        const options = await page.$$('div[role="option"], .mantine-Select-item, .mantine-Select-option, [data-combobox-option]');
                                        for (const opt of options) {
                                            const text = await page.evaluate(el => (el.textContent || '').trim().toLowerCase(), opt);
                                            const matchLower = matchText.toLowerCase();
                                            
                                            let matched = false;
                                            if (text === matchLower) {
                                                matched = true;
                                            } else if (placeholder === 'Tgl' || placeholder === 'Thn') {
                                                if (parseInt(text, 10) === parseInt(matchLower, 10)) {
                                                    matched = true;
                                                }
                                            } else if (text.includes(matchLower) && placeholder === 'Bln') {
                                                matched = true;
                                            }

                                            if (matched) {
                                                await page.evaluate(el => {
                                                    el.scrollIntoView({ behavior: 'instant', block: 'center' });
                                                    el.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
                                                    el.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                                                    el.dispatchEvent(new PointerEvent('pointerup', { bubbles: true }));
                                                    el.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
                                                    el.click();
                                                }, opt);
                                                await sleep(400);
                                                return;
                                            }
                                        }
                                        // Click outside or Escape if not found to close dropdown
                                        await page.keyboard.press('Escape');
                                        await sleep(300);
                                    }
                                };

                                let isFormValid = false;
                                for (let formRetry = 0; formRetry < 3; formRetry++) {
                                    await clickMantineSelect('Tgl', tglNum);
                                    await clickMantineSelect('Bln', blnTxt);
                                    await clickMantineSelect('Thn', tahun);
                                    
                                    await sleep(1000);

                                    const nextBtn = await findButtonByKeywords(page, ['SELANJUTNYA', 'SIMPAN']);
                                    if (nextBtn) {
                                        await nextBtn.click();
                                        await sleep(2000);
                                    }

                                    const perbaruiBtn = await findButtonByKeywords(page, ['YA, PERBARUI DATA', 'YA', 'PERBARUI']);
                                    if (perbaruiBtn) {
                                        await perbaruiBtn.click();
                                        await sleep(2000);
                                    }
                                    
                                    const validationStatus = await page.evaluate(() => {
                                        const txt = document.body.textContent.toLowerCase();
                                        if (txt.includes('berhasil diperbarui')) {
                                            return 'success'; // Sukses, popup muncul
                                        }
                                        if (txt.includes('wajib diisi')) {
                                            return 'error'; // Ada error validasi
                                        }
                                        return 'unknown';
                                    });
                                    
                                    if (validationStatus === 'success' || validationStatus === 'unknown') {
                                        isFormValid = true;
                                        break;
                                    } else {
                                        console.log(`[Self-Healing ${formRetry + 1}/3] Terdeteksi pesan "Wajib diisi". Bot mencoba mengisi ulang form...`);
                                        await sleep(1500);
                                    }
                                }
                                
                                if (!isFormValid) {
                                    console.log('Peringatan: Bot gagal menembus form update data setelah 3x percobaan karena validasi Wajib Diisi.');
                                }
                            } else {
                                await sleep(1000);
                                const nextBtn = await findButtonByKeywords(page, ['SELANJUTNYA', 'SIMPAN']);
                                if (nextBtn) {
                                    await nextBtn.click();
                                    await sleep(2000);
                                }
                                const perbaruiBtn = await findButtonByKeywords(page, ['YA, PERBARUI DATA', 'YA', 'PERBARUI']);
                                if (perbaruiBtn) {
                                    await perbaruiBtn.click();
                                    await sleep(2000);
                                }
                            }
                            
                            // Re-evaluate state
                            let newState = null;
                            for (let attempt = 0; attempt < 15; attempt++) {
                                const pageData = await page.evaluate(() => {
                                    const txt = document.body.textContent.toLowerCase();
                                    if (txt.includes('berhasil diperbarui')) {
                                        return { state: 'success_modal' };
                                    }
                                    if (txt.includes('pernyataan persetujuan') || txt.includes('saya telah membaca')) {
                                        return { state: 'pernyataan' };
                                    }
                                    if (txt.includes('pilihan jenis pelanggan') || txt.includes('pelanggan terdaftar')) {
                                        return { state: 'modal' };
                                    }
                                    if (txt.includes('melebihi batas kewajaran') || txt.includes('batas kewajaran')) {
                                        return { state: 'limit' };
                                    }
                                    const isLogin = txt.includes('nomor ponsel atau email') && txt.includes('merchant apps');
                                    if (isLogin) return { state: 'login' };

                                    const hasCekPesanan = txt.includes('cek pesanan') || txt.includes('jumlah produk');
                                    if (hasCekPesanan) return { state: 'ready' };
                                    return { state: 'waiting' };
                                });
                                if (pageData.state !== 'waiting') {
                                    newState = pageData.state;
                                    break;
                                }
                                await sleep(500);
                            }
                            if (newState) {
                                searchState = newState;
                            } else {
                                throw new Error(`Status halaman tidak merespon setelah diklik (stuck di ${searchState}).`);
                            }
                            if (searchState === 'incomplete_data') {
                                await page.screenshot({ path: `debug-incomplete-${trx.nik}-${Date.now()}.png` });
                                console.log('📸 Screenshot gagal update form disimpan.');
                            }
                            continue; // Re-evaluate loop
                        }

                        if (searchState === 'modal') {
                            console.log('Mendeteksi modal pendaftaran/pemilihan jenis pelanggan...');
                            
                            // Mendeteksi opsi yang tersedia di layar agar tidak menebak-nebak
                            const detectedOption = await page.evaluate(() => {
                                const isVisible = (el) => {
                                    const rect = el.getBoundingClientRect();
                                    return rect.width > 0 && rect.height > 0 && window.getComputedStyle(el).display !== 'none';
                                };
                                const elements = Array.from(document.querySelectorAll('*'));
                                let hasRumahTangga = false;
                                let hasUsahaMikro = false;
                                
                                for (const el of elements) {
                                    if (!isVisible(el) || el.children.length > 2) continue;
                                    const txt = (el.textContent || '').trim().toLowerCase();
                                    if (txt === 'rumah tangga') hasRumahTangga = true;
                                    if (txt === 'usaha mikro') hasUsahaMikro = true;
                                }
                                
                                if (hasRumahTangga) return 'Rumah Tangga';
                                if (hasUsahaMikro) return 'Usaha Mikro';
                                return null;
                            });

                            if (detectedOption) {
                                console.log(`✓ Sistem mendeteksi ketersediaan opsi "${detectedOption}". Memilih opsi tersebut...`);
                                const clicked = await page.evaluate((opt) => {
                                    const elements = Array.from(document.querySelectorAll('*'));
                                    let targetEl = null;

                                    for (const el of elements) {
                                        if (el.children.length > 2) continue;
                                        if ((el.textContent || '').trim().toLowerCase() === opt.toLowerCase()) {
                                            targetEl = el;
                                            break;
                                        }
                                    }

                                    if (targetEl) {
                                        // Coba cari tombol 'Pilih' di dalam parent terdekat (hingga 3 level ke atas)
                                        let currentParent = targetEl.parentElement;
                                        for (let i = 0; i < 3; i++) {
                                            if (!currentParent) break;
                                            const pilihElements = Array.from(currentParent.querySelectorAll('*')).filter(e => {
                                                return (e.textContent || '').trim().toLowerCase() === 'pilih';
                                            });
                                            if (pilihElements.length > 0) {
                                                // Ambil elemen terdalam yang teksnya persis 'pilih'
                                                pilihElements[pilihElements.length - 1].click();
                                                return true;
                                            }
                                            currentParent = currentParent.parentElement;
                                        }
                                        
                                        // Fallback: klik elemen teks itu sendiri
                                        targetEl.click();
                                        return true;
                                    }
                                    return false;
                                }, detectedOption);
                                
                                if (clicked) {
                                    await sleep(1000);
                                } else {
                                    console.log(`! Gagal mengklik opsi "${detectedOption}". Mencoba klik tombol Pilih secara umum...`);
                                    const pilihBtn = await findButtonByKeywords(page, ['Pilih']);
                                    if (pilihBtn) {
                                        await pilihBtn.click();
                                        await sleep(1000);
                                    }
                                }
                            } else {
                                console.log(`! Sistem tidak dapat mendeteksi opsi secara spesifik. Fallback mencoba memilih tombol Pilih...`);
                                const pilihBtn = await findButtonByKeywords(page, ['Pilih']);
                                if (pilihBtn) {
                                    await pilihBtn.click();
                                    await sleep(1000);
                                }
                            }

                            console.log('Mencari tombol pendaftaran...');
                            const lanjutkanBtn = await findButtonByKeywords(page, ['DAFTARKAN PELANGGAN', 'LANJUTKAN PENJUALAN', 'LANJUTKAN TRANSAKSI', 'DAFTARKAN']);
                            if (lanjutkanBtn) {
                                await lanjutkanBtn.click();
                                await sleep(1500);
                            }

                            // Re-evaluate state
                            let newState = null;
                            for (let attempt = 0; attempt < 20; attempt++) {
                                const pageData = await page.evaluate(() => {
                                    const txt = document.body.textContent.toLowerCase();
                                    if (txt.includes('pernyataan persetujuan') || txt.includes('saya telah membaca')) {
                                        return { state: 'pernyataan' };
                                    }
                                    if (txt.includes('data pelanggan belum lengkap') || txt.includes('lengkapi data dahulu')) {
                                        return { state: 'incomplete_data' };
                                    }
                                    if (txt.includes('melebihi batas kewajaran') || txt.includes('batas kewajaran')) {
                                        return { state: 'limit' };
                                    }
                                    if (txt.includes('stok tabung kosong') || txt.includes('stok tabung yang dapat dijual kosong')) {
                                        return { state: 'stok_kosong' };
                                    }
                                    const isLogin = txt.includes('nomor ponsel atau email') && txt.includes('merchant apps');
                                    if (isLogin) return { state: 'login' };

                                    const hasCekPesanan = txt.includes('cek pesanan') || txt.includes('jumlah produk');
                                    if (hasCekPesanan) return { state: 'ready' };
                                    return { state: 'waiting' };
                                });

                                if (pageData.state === 'stok_kosong') {
                                    throw new Error('FATAL: STOK_MYPERTAMINA_KOSONG');
                                }

                                if (pageData.state !== 'waiting') {
                                    newState = pageData.state;
                                    break;
                                }
                                await sleep(500);
                            }
                            
                            if (newState) {
                                searchState = newState;
                            } else {
                                throw new Error(`Status halaman tidak merespon setelah diklik (stuck di ${searchState}).`);
                            }
                            continue;
                        }

                        // If not pernyataan, incomplete_data, success_modal, or modal, break out of loop to process
                        resolvingPreStates = false;
                    }

                    // --- HANDLE B: LIMIT BULANAN TERLAMPAUI ---
                    if (searchState === 'limit') {
                        console.log('Mendeteksi peringatan batas kewajaran bulan ini terpenuhi.');
                        await api.post(`/api/gagal-lapor/${trx.id}`, {
                            message: 'Limit batas kewajaran Pertamina'
                        });
                        limitCount++;
                        await sleep(1500);
                        continue;
                    }

                    if (searchState !== 'ready') {
                        throw new Error(`Gagal mencapai form transaksi (Status tersangkut di: ${searchState}). Tombol "CEK PESANAN" tidak ditemukan.`);
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

                    let cekPesananBtn = null;
                    for (let attempt = 0; attempt < 20; attempt++) {
                        // Cek apakah ada popup stok kosong menutupi
                        const isStokKosongPopup = await page.evaluate(() => {
                            const txt = document.body.textContent.toLowerCase();
                            return txt.includes('stok tabung kosong') || txt.includes('stok tabung yang dapat dijual kosong');
                        });
                        if (isStokKosongPopup) {
                            throw new Error('FATAL: STOK_MYPERTAMINA_KOSONG');
                        }

                        cekPesananBtn = await findButtonByKeywords(page, ['CEK PESANAN']);
                        if (cekPesananBtn) break;
                        await sleep(500);
                    }
                    if (!cekPesananBtn) {
                        await page.screenshot({ path: `debug-cek-pesanan-${trx.nik}-${Date.now()}.png` });
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
                    let solverTries = 0;

                    while (!isCaptchaSolved) {
                        // Cari frame captcha secara cerdas (mendukung iframe)
                        const captchaFrame = await findCaptchaFrame(page);
                        const hasCaptcha = (captchaFrame !== null);

                        if (!hasCaptcha) {
                            let backToInputPage = false;
                            try {
                                backToInputPage = await page.evaluate(() => {
                                    const input = document.querySelector('input[type="text"]');
                                    const hasPassword = !!document.querySelector('input[type="password"]');
                                    return !!input && !hasPassword && !document.body.textContent.includes('PROSES PENJUALAN');
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

                                if (!isStillOnSummary) {
                                    console.log('✓ Captcha selesai, halaman berpindah ke riwayat/dashboard.');
                                    isCaptchaSolved = true;
                                } else {
                                    const pageText = await page.evaluate(() => document.body.textContent.toLowerCase());
                                    const pageUrl = page.url().toLowerCase();
                                    if (pageUrl.includes('/login') || pageText.includes('masuk ke akun') || pageText.includes('selamat datang')) {
                                        throw new Error('Sesi login berakhir saat memproses transaksi.');
                                    } else if (pageText.includes('stok tabung kosong') || pageText.includes('stok tabung yang dapat dijual kosong') || pageText.includes('lakukan penebusan')) {
                                        throw new Error('FATAL: STOK_MYPERTAMINA_KOSONG');
                                    } else if (pageText.includes('gagal') || pageText.includes('error') || pageText.includes('kesalahan') || pageText.includes('tidak berhasil')) {
                                        throw new Error('Transaksi gagal diproses oleh portal Pertamina (Terdeteksi error/kegagalan pada halaman).');
                                    } else {
                                        isCaptchaSolved = true;
                                        console.log('✓ Halaman bergeser. Transaksi selesai diproses.');
                                    }
                                }
                            }
                        } else {
                            // Coba jalankan sistem In-House Solver kita
                            if (solverTries < 9999) {
                                solverTries++;
                                console.log(`Mencoba meretas Jigsaw Captcha (Percobaan ke-${solverTries})...`);
                                const solveSuccess = await autoSolveSliderCaptcha(page);
                                if (!solveSuccess && solverTries >= 9999) {
                                    console.log('Bot In-House menyerah setelah terlalu banyak gagal. Menunggu bantuan manual Anda di layar browser...');
                                }
                            } else {
                                await sleep(1500);
                                captchaCheckAttempts++;
                                if (captchaCheckAttempts % 8 === 0) {
                                    console.log('Sedang menunggu Anda menggeser puzzle CAPTCHA secara manual...');
                                }
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
                    } catch (e) { }

                } catch (err) {
                    console.error(`✗ Gagal memproses NIK ${trx.nik}:`, err.message);

                    // --- RECOVERY: Cek apakah dipental ke halaman login ---
                    let isLoginPage = false;
                    try {
                        const currentUrl = page.url().toLowerCase();
                        if (currentUrl.includes('login')) {
                            isLoginPage = true;
                        } else {
                            isLoginPage = await page.evaluate(() => {
                                const inputs = Array.from(document.querySelectorAll('input'));
                                const hasPassword = inputs.some(input => (input.getAttribute('type') || '').toLowerCase() === 'password');
                                const txt = document.body.textContent.toLowerCase();
                                const hasLoginText = txt.includes('masuk ke akun') || txt.includes('selamat datang');
                                return hasPassword || hasLoginText;
                            });
                        }
                    } catch (e) {
                        try {
                            if (page.url().toLowerCase().includes('login')) {
                                isLoginPage = true;
                            }
                        } catch (err2) {}
                    }

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
                            } catch (e) { }
                            i--; // Mundurkan index i agar memproses NIK yang sama di iterasi berikutnya
                            continue;
                        } else {
                            console.log('❌ Gagal memproses setelah 2 kali percobaan ulang.');
                        }
                    }

                    if (err.message.includes('STOK_MYPERTAMINA_KOSONG')) {
                        console.log('Menerima sinyal Stok Kosong. Melaporkan ke database dan membatalkan sisa antrean pangkalan ini...');
                        pangkalanStokHabis = true;
                        if (!pangkalanHabisList.includes(merchantName)) pangkalanHabisList.push(merchantName);
                        try {
                            await api.post('/api/rpa-sync/stok-kosong', {
                                akun_pangkalan_id: pangkalan.id,
                                message: 'Stok tabung LPG 3kg habis di pangkalan. Harap lakukan penebusan (Beli stok) ke Agen.'
                            });
                        } catch (e) {}
                    } else if (err.message.includes('FATAL_LIMIT_EXCEEDED')) {
                        console.log('⚠️ Pelanggan melebihi batas kewajaran pembelian (Limit Habis Nasional). Melaporkan ke database...');
                        limitCount++;
                        try {
                            await api.post(`/api/gagal-lapor/${trx.id}`, {
                                message: `Gagal: Melebihi batas kewajaran pembelian LPG 3 kg bulan ini.`
                            });
                        } catch (e) { }
                    } else {
                        failCount++;
                        try {
                            await api.post(`/api/gagal-lapor/${trx.id}`, {
                                message: `Error Bot: ${err.message}`
                            });
                        } catch (e) { }
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
                        } catch (err2) { }
                    }
                }
            }

            if (isAbortedByUser) {
                console.log('Menutup browser pangkalan ini (Dihentikan Manual)...');
                await browser.close();
                activeBrowser = null;
                break; // Hentikan loop pangkalan, keluar dari job ini
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
        
        pangkalanSisaList = pangkalanSisaList.filter(p => !pangkalanHabisList.includes(p));
        let semuaHabis = (pangkalanHabisList.length > 0 && pangkalanSisaList.length === 0);

        if (semuaHabis) {
            completedSuccessfully = true;
        }

        const syncDetail = await api.get(`/api/rpa-sync/${syncId}`).then(res => res.data.sync).catch(() => null);
        if (syncDetail) {
            limitCount = syncDetail.limit_records ?? limitCount;
            failCount = syncDetail.failed_records ?? failCount;
            successCount = syncDetail.success_records ?? successCount;
        }

        const statusAkhir = completedSuccessfully ? 'completed' : 'failed';
        let detailHitungan = limitCount > 0 
            ? `Berhasil: ${successCount}, Limit: ${limitCount}, Gagal: ${failCount}` 
            : `Berhasil: ${successCount}, Gagal: ${failCount}`;
        let pesanAkhir = `Sinkronisasi selesai. ${detailHitungan}.`;
        
        if (!completedSuccessfully) {
            if (successCount === 0 && failCount === 0 && limitCount === 0) {
                pesanAkhir = `Proses dihentikan (Gagal).`;
            } else if (failCount === 0 && limitCount === 0 && successCount > 0) {
                pesanAkhir = `Sebagian selesai (Selesai ${successCount}).`;
            }
        } else {
             if (successCount === 0 && failCount === 0 && limitCount === 0 && semuaHabis) {
                 pesanAkhir = `Proses dihentikan. Stok tabung LPG di semua pangkalan Anda sudah habis (0).`;
             } else if (successCount > 0 && failCount === 0 && limitCount === 0 && semuaHabis) {
                 pesanAkhir = `Sebagian selesai (Selesai ${successCount}). Bot berhenti karena stok Pangkalan habis.`;
             }
        }
        
        let statusPangkalanDesc = "";
        if (pangkalanHabisList.length > 0 || pangkalanSisaList.length > 0) {
            let descParts = [];
            if (pangkalanSisaList.length > 0) {
                descParts.push(`Ada Sisa: ${pangkalanSisaList.join(', ')}`);
            }
            if (pangkalanHabisList.length > 0) {
                descParts.push(`Habis: ${pangkalanHabisList.join(', ')}`);
            }
            statusPangkalanDesc = ` [Info Stok - ${descParts.join(' | ')}]`;
        }
        
        pesanAkhir += statusPangkalanDesc;

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
        return true;

    } catch (error) {
        console.error('\n✗ Terjadi Kesalahan Utama pada Bot:', error.message);
        if (activeBrowser) {
            try { await activeBrowser.close(); } catch (e) {}
            activeBrowser = null;
        }
        if (activeSyncId) {
            await api.patch(`/api/rpa-sync/${activeSyncId}`, {
                status: 'failed',
                message: 'Bot Error: ' + error.message
            }).catch(() => {});
            activeSyncId = null;
        }
        return true;
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

// Periksa argumen untuk run-once
const isRunOnce = process.argv.includes('--run-once');

if (isRunOnce) {
    (async () => {
        console.log('Menjalankan Bot dalam Mode RUN-ONCE (Satu kali eksekusi)...');
        console.log('Menunggu persiapan data (1.5 detik) agar sinkronisasi database selesai...');
        await sleep(1500); // Mencegah race condition dengan proses save() di Laravel
        await run();
        console.log('Tugas selesai. Menutup terminal...');
        process.exit(0);
    })();
} else {
    startDaemon();
}
