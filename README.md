# GAS-RPA: Enterprise LPG 3Kg Distribution Sync System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white" alt="Vue.js">
  <img src="https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia.js">
  <img src="https://img.shields.io/badge/Node.js-339933?style=for-the-badge&logo=nodedotjs&logoColor=white" alt="Node.js">
  <img src="https://img.shields.io/badge/Puppeteer-40B5A4?style=for-the-badge&logo=puppeteer&logoColor=white" alt="Puppeteer">
  <img src="https://img.shields.io/badge/PM2-2B037A?style=for-the-badge&logo=pm2&logoColor=white" alt="PM2">
</p>

---

## Deskripsi Proyek

**GAS-RPA** adalah platform otomasi dan sinkronisasi distribusi LPG 3 Kg bersubsidi tingkat *Enterprise* dengan dukungan Multi-Tenant (SaaS). Sistem ini menghubungkan database transaksi lokal (POS) dari berbagai Agen dengan portal resmi **MAP MyPertamina** secara otomatis menggunakan robot RPA (Robotic Process Automation) yang berjalan 24/7 di latar belakang.

Dilengkapi dengan sistem Antrean Terpusat (*Queue Worker*) dan Keamanan Tingkat Militer (*Encryption at Rest*), aplikasi ini dirancang untuk menangani beban tinggi secara efisien dan mengamankan data privasi warga.

---

## Tech Stack & Arsitektur

### 1. Backend & Frontend (Laravel + Vue Inertia)
*   **Framework:** Laravel 11
*   **Frontend UI:** Vue.js 3 + Inertia.js (SPA Cepat dan Dinamis tanpa *reload*)
*   **Database:** MySQL / MariaDB
*   **Keamanan Data (Level 2):** Enkripsi *AES-256-CBC* untuk Kolom Sensitif (NIK & Password Pertamina), dilengkapi dengan sistem pencarian mutakhir menggunakan algoritma sidik jari *SHA-256 Blind Index*.
*   **Multi-Tenant:** Hierarki peran `Super Admin` dan `Agen`. Super Admin dapat memantau kesehatan server, log transaksi global (*live feed*), dan metrik bot.
*   **Authentication:** API Key secure header token validation untuk komunikasi Bot.

### 2. RPA Automation Bot (Node.js + PM2)
*   **Runtime:** Node.js v18+
*   **Process Manager:** PM2 (Berjalan senyap di *background* sebagai Daemon Worker, mendukung *Cluster Mode* untuk beban tinggi).
*   **Automation Engine:** Puppeteer (Mode *Headless* untuk otomatisasi di belakang layar tanpa mengganggu aktivitas PC).
*   **Kecerdasan Bot:** Fitur In-House Computer Vision Solver untuk MyPertamina Jigsaw Captcha.

---

## Fitur Utama

-   **PM2 Queue System (Sistem Antrean Beban):** Mampu melayani ratusan agen sekaligus tanpa merusak server. Jika agen memicu tombol sinkronisasi bersamaan, PM2 akan membagi beban secara adil (misal 4 bot jalan bersamaan, sisanya menunggu giliran di antrean).
-   **Enkripsi Data Sensitif:** Seluruh NIK Pelanggan dan Kredensial Pangkalan di-*database* berubah menjadi sandi acak. Privasi 100% terjaga sekalipun *database* berhasil diunduh secara ilegal oleh *hacker*.
-   **Dashboard Super Admin (Command Center):** Memantau pemakaian memori server (*RAM Allocation*), trafik transaksi harian/bulanan, dan Log Live dari seluruh Agen.
-   **Deteksi Otomatis & Recovery:**
    -   *Self-Healing Engine:* Menggunakan *state-machine loop* dinamis untuk menangani urutan pop-up yang muncul secara acak (Update Data, Pernyataan Persetujuan, dll) serta otomatis melakukan *refresh* (muat ulang) halaman secara mandiri saat koneksi internet buruk.
    -   *Stok Kosong:* Otomatis mendeteksi jika stok di portal MyPertamina habis, dan beralih ke pangkalan berikutnya.
    -   *Batas Kuota Bulanan:* Mendeteksi batas limit 4 tabung/bulan.
    -   *Invalid NIK:* Memblokir (nonaktif) status pelanggan lokal secara spesifik hanya jika NIK ditolak/tidak terdaftar di server pusat.
-   **History Preservation (Penyimpanan Riwayat):** Otomatis membersihkan antrean transaksi yang gagal atau kedaluwarsa setiap pergantian bulan/hari, namun secara permanen menyimpan jejak seluruh riwayat transaksi yang sukses.

---

## Langkah Instalasi & Pengaturan

### 1. Setup Backend (Laravel)
1. Masuk ke folder root project dan pasang dependensi:
   ```bash
   composer install
   npm install
   ```
2. Konfigurasi file `.env`:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```
   Sesuaikan `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Jangan lupa isi `LPG_API_KEY`.
3. Jalankan migrasi database:
   ```bash
   php artisan migrate
   ```
4. Build aset Frontend (Vue):
   ```bash
   npm run build
   ```
5. Jalankan server lokal:
   ```bash
   php artisan serve
   ```

### 2. Setup RPA Bot (PM2 Daemon)
1. Masuk ke folder bot dan pasang dependensi:
   ```bash
   cd rpa-bot
   npm install
   npm install -g pm2
   ```
2. Konfigurasi file `.env` di dalam folder `rpa-bot`:
   ```bash
   copy .env.example .env
   ```
   Pastikan `LARAVEL_APP_URL` dan `LPG_API_KEY` sama persis dengan yang di sisi Laravel.
3. Jalankan PM2 Worker (Contoh: Menjalankan 4 pekerja bayangan):
   ```bash
   pm2 start bot.js --name "gas-rpa-worker" -i 4
   pm2 save
   ```
   Kini bot sudah aktif 24 jam penuh di komputer Anda dan siap menerima antrean dari Laravel!

---

## Lisensi

Proyek ini menggunakan lisensi **MIT License**. Anda bebas menggunakan, memodifikasi, dan mendistribusikannya kembali.
