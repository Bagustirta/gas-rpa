# GAS-RPA: Automated LPG 3Kg Distribution Sync System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Node.js-339933?style=for-the-badge&logo=nodedotjs&logoColor=white" alt="Node.js">
  <img src="https://img.shields.io/badge/Puppeteer-40B5A4?style=for-the-badge&logo=puppeteer&logoColor=white" alt="Puppeteer">
  <img src="https://img.shields.io/badge/Filament_PHP-EBB30A?style=for-the-badge&logo=laravel&logoColor=black" alt="Filament PHP">
  <img src="https://img.shields.io/badge/WebSockets-Reverb-blue?style=for-the-badge" alt="WebSockets">
</p>

---

## Deskripsi Proyek

**GAS-RPA** adalah platform otomasi dan sinkronisasi distribusi LPG 3 Kg bersubsidi terintegrasi. Sistem ini menghubungkan database transaksi lokal (POS) Anda dengan portal resmi **MAP MyPertamina** secara otomatis menggunakan robot RPA (Robotic Process Automation). 

Dengan sistem ini, pemilik pangkalan atau sub-penyalur dapat menghemat waktu pelaporan harian dari berjam-jam menjadi hanya beberapa klik saja, mengurangi risiko kesalahan input manusia (*human error*), serta memantau progres pelaporan secara real-time.

---

## Tech Stack & Arsitektur

Sistem ini terbagi menjadi dua bagian utama yang berkomunikasi secara aman melalui REST API:

### 1. Backend & Dashboard Panel (Laravel)
*   **Framework:** Laravel 11
*   **Admin Panel:** Filament PHP (Sleek, responsive, dan mudah digunakan)
*   **Database:** MySQL / MariaDB (Untuk menyimpan data pelanggan, riwayat transaksi, dan antrean sinkronisasi)
*   **Real-time Broadcast:** Laravel Reverb (WebSocket server bawaan Laravel untuk memancarkan progres audit/lapor secara langsung ke browser pengguna)
*   **Authentication:** API Key secure header token validation.

### 2. RPA Automation Bot (Node.js)
*   **Runtime:** Node.js v18+
*   **Automation Engine:** Puppeteer (Mengontrol browser Google Chrome untuk mensimulasikan pengetikan, navigasi, dan klik layaknya manusia)
*   **HTTP Client:** Axios (Mengambil data antrean NIK siap lapor dan mengirimkan status laporan balik ke Laravel)
*   **Keamanan & Anti-Detection:** Dilengkapi fitur ketik dengan delay acak, pembersih state input React, serta deteksi otomatis Captcha keamanan untuk intervensi manual pengguna.

---

## Fitur Utama

-   **Otomasi Login Berantai (Multi-Account):** Bot dapat melakukan login berturut-turut ke berbagai akun pangkalan terdaftar secara otomatis tanpa intervensi berulang.
-   **Smart Transaction Queue:** Sistem secara cerdas menyaring NIK yang belum dilaporkan atau berstatus gagal untuk diproses kembali.
-   **Deteksi Otomatis & Recovery:**
    -   *Stok Kosong:* Otomatis mendeteksi jika stok di portal MyPertamina habis, mengirim sinyal ke Laravel, dan memproses akun pangkalan berikutnya.
    -   *Batas Kuota Bulanan (Limit):* Mendeteksi jika NIK pelanggan telah melampaui limit pembelian nasional (kuota 4/4 habis) dan menandainya secara tepat di database.
    -   *NIK Tidak Valid/Meninggal:* Otomatis mendeteksi error dari portal Pertamina (salah format, pelanggan meninggal, NIB kurang) dan menonaktifkan status aktif pelanggan tersebut secara otomatis di database lokal.
-   **Live Monitoring Dashboard:** Progres bot saat mengetik, mencari NIK, memproses kuota, hingga CAPTCHA dapat dipantau langsung dari dashboard Laravel menggunakan WebSockets.

---

## Alur Kerja Sistem

Sistem sinkronisasi bekerja melalui alur sebagai berikut:

1. **Membuat Antrean Pekerjaan**: Pengguna memicu sinkronisasi dari Dashboard Laravel, yang akan membuat antrean pekerjaan (*Sync Job*) di database.
2. **Mengambil Data Antrean**: Bot Node.js mengambil data transaksi siap lapor dan akun pangkalan dari API Laravel secara berkala.
3. **Otomatisasi Input**: Bot membuka browser via Puppeteer, membantu pengguna masuk ke portal MyPertamina, lalu mengisi NIK dan jumlah tabung transaksi secara otomatis.
4. **Update Status Real-Time**: Hasil akhir transaksi dilaporkan kembali ke API Laravel untuk memperbarui database dan memancarkan progres audit secara live ke browser admin menggunakan WebSockets (Laravel Reverb).

---

## Langkah Instalasi & Pengaturan

### 1. Setup Backend (Laravel)
1. Masuk ke folder root project:
   ```bash
   cd d:\laragon\www\gas
   ```
2. Pasang dependensi composer:
   ```bash
   composer install
   ```
3. Konfigurasi file `.env`:
   ```bash
   copy .env.example .env
   ```
   Sesuaikan `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan konfigurasi `REVERB_*` untuk WebSocket. Jangan lupa isi `LPG_API_KEY` untuk token keamanan bot.
4. Jalankan migrasi database:
   ```bash
   php artisan migrate
   ```
5. Buat user admin Filament:
   ```bash
   php artisan make:filament-user
   ```
6. Jalankan server lokal:
   ```bash
   php artisan serve
   ```

### 2. Setup RPA Bot (Node.js)
1. Masuk ke folder bot:
   ```bash
   cd d:\laragon\www\gas\rpa-bot
   ```
2. Pasang dependensi node modules:
   ```bash
   npm install
   ```
3. Konfigurasi file `.env` untuk bot:
   ```bash
   copy .env.example .env
   ```
   Buka file `.env` dan sesuaikan:
   *   `LARAVEL_APP_URL` (contoh: `http://localhost:8000`)
   *   `LPG_API_KEY` (harus sama dengan yang di Laravel)
   *   `PERTAMINA_PORTAL_URL` (`https://subsiditepat.mypertamina.id/LPG`)
4. Jalankan bot:
   ```bash
   npm start
   ```

---

## Lisensi

Proyek ini menggunakan lisensi **MIT License**. Anda bebas menggunakan, memodifikasi, dan mendistribusikannya kembali.
