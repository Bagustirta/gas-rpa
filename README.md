# GAS-RPA: Enterprise LPG 3Kg Distribution Sync System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white" alt="Vue.js">
  <img src="https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia.js">
  <img src="https://img.shields.io/badge/Node.js-339933?style=for-the-badge&logo=nodedotjs&logoColor=white" alt="Node.js">
  <img src="https://img.shields.io/badge/Puppeteer-40B5A4?style=for-the-badge&logo=puppeteer&logoColor=white" alt="Puppeteer">
  <img src="https://img.shields.io/badge/PM2-2B037A?style=for-the-badge&logo=pm2&logoColor=white" alt="PM2">
  <img src="https://img.shields.io/badge/Google%20Gemini-4285F4?style=for-the-badge&logo=google&logoColor=white" alt="Gemini AI">
  <img src="https://img.shields.io/badge/Telegram-26A5E4?style=for-the-badge&logo=telegram&logoColor=white" alt="Telegram">
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

### 3. AI Self-Healing Watchdog (Node.js + Gemini AI + Telegram)
*   **Watchdog Engine:** `watchdog.js` — daemon *event-driven* yang memantau log error secara real-time menggunakan `fs.watchFile` (tanpa polling agresif).
*   **AI Diagnosis:** Google Gemini 3.5 Flash Lite digunakan untuk menganalisis error baru yang belum pernah terjadi sebelumnya dan memberikan saran perbaikan otomatis.
*   **Notifikasi:** Alert instan via Telegram Bot setiap kali terjadi anomali, error kritis, atau *stuck queue*.
*   **Remote Control:** Perintah Telegram (`/status`, `/restart`, `/test`, `/help`) untuk memantau dan mengontrol sistem dari HP tanpa perlu buka laptop.

---

## Arsitektur Auto-Scaling & Watchdog

```
┌─────────────────────────────────────────────────────────┐
│                  Laravel Backend                         │
│  (Push Event via WebSocket saat job masuk antrian)       │
└──────────────┬───────────────────────────────────────────┘
               │ WebSocket Push / HTTP Poll (fallback)
               ▼
┌──────────────────────────────┐
│      rpa-scaler (PM2)        │  ← scaler.js
│  - Terima sinyal job baru    │
│  - Sinkronisasi state PM2    │  ← syncActiveWorkers()
│    (baca pm2 jlist real-time)│
│  - Spawn/kill rpa-bot-N      │
└──────────────┬───────────────┘
               │ Kelola proses
               ▼
┌──────────────────────────────┐
│   rpa-bot-1 ... rpa-bot-N    │  ← bot.js (Puppeteer)
│   (Auto-scaled berdasarkan   │
│    jumlah job di antrian)    │
└──────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│               rpa-watchdog (PM2)                          │  ← watchdog.js
│  - fs.watchFile pada PM2 error log & laravel.log          │
│  - Deteksi anomali → kirim ke Gemini AI untuk diagnosis   │
│  - Kirim alert ke Telegram Bot (@gas_rpa_anom_bot)        │
│  - Terima perintah /status /restart /test /help           │
│  - Safety net: cek stuck queue setiap 15 menit            │
└──────────────────────────────────────────────────────────┘
```

---

## Fitur Utama

-   **PM2 Queue System (Sistem Antrean Beban):** Mampu melayani ratusan agen sekaligus tanpa merusak server. Jika agen memicu tombol sinkronisasi bersamaan, PM2 akan membagi beban secara adil (misal 4 bot jalan bersamaan, sisanya menunggu giliran di antrean).
-   **Dynamic Excel Import/Export:** Sistem impor/ekspor data pelanggan yang sangat fleksibel. Mampu membaca file Excel dengan berbagai kombinasi kolom (NIK, Status, Tempat Lahir, Tanggal Lahir) tanpa perlu urutan kaku berkat fitur deteksi judul kolom (*Smart Header Detection*). Data ganda akan otomatis menimpa/memperbarui data lama tanpa duplikasi.
-   **Smart NIK Parsing (Auto-Sync):** Sistem dapat secara cerdas membaca dan mensinkronisasi (*sync*) informasi tersembunyi di balik 16 digit NIK pelanggan. Hanya dengan menginput NIK, sistem akan otomatis melengkapi data Provinsi, Kabupaten/Kota, Kecamatan, Tanggal Lahir, hingga Jenis Kelamin pelanggan.
-   **Enkripsi Data Sensitif:** Seluruh NIK Pelanggan dan Kredensial Pangkalan di-*database* berubah menjadi sandi acak. Privasi 100% terjaga sekalipun *database* berhasil diunduh secara ilegal oleh *hacker*.
-   **Dashboard Super Admin (Command Center):** Memantau pemakaian memori server (*RAM Allocation*), trafik transaksi harian/bulanan, dan Log Live dari seluruh Agen.
-   **Deteksi Otomatis & Recovery:**
    -   *Self-Healing Engine:* Menggunakan *state-machine loop* dinamis untuk menangani urutan pop-up yang muncul secara acak (Update Data, Pernyataan Persetujuan, dll) serta otomatis melakukan *refresh* (muat ulang) halaman secara mandiri saat koneksi internet buruk.
    -   *Stok Kosong:* Otomatis mendeteksi jika stok di portal MyPertamina habis, dan beralih ke pangkalan berikutnya.
    -   *Batas Kuota Bulanan:* Mendeteksi batas limit 4 tabung/bulan.
    -   *Invalid NIK:* Memblokir (nonaktif) status pelanggan lokal secara spesifik hanya jika NIK ditolak/tidak terdaftar di server pusat.
-   **AI Watchdog (Self-Healing Canggih):** Daemon *event-driven* memantau log error secara real-time. Jika ada bug baru yang terdeteksi, Gemini AI menganalisis error dan memberikan saran perbaikan. Alert otomatis dikirim ke HP via Telegram Bot. Mendukung perintah remote `/status`, `/restart`, `/test`.
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

3. Tambahkan konfigurasi Telegram & Gemini AI di `rpa-bot/.env`:
   ```env
   TELEGRAM_BOT_TOKEN=token_bot_telegram_anda
   TELEGRAM_CHAT_ID=chat_id_anda
   GEMINI_API_KEY=api_key_gemini_anda
   ```
   > Lihat bagian **"Konfigurasi Watchdog AI"** di bawah untuk cara mendapatkan nilai-nilai ini.

4. Jalankan seluruh daemon via `ecosystem.json`:
   ```bash
   # Dari folder root project (bukan rpa-bot/)
   pm2 startOrRestart ecosystem.json
   pm2 save
   ```
   Perintah ini akan menjalankan `rpa-scaler` dan `rpa-watchdog` secara otomatis. Worker `rpa-bot-N` akan di-*spawn* secara dinamis oleh scaler saat ada job masuk.

5. *(Opsional — Windows)* Gunakan `start-server.bat` untuk menjalankan semua layanan sekaligus saat komputer dinyalakan:
   ```bat
   start-server.bat
   ```

### 3. Konfigurasi Watchdog AI (Telegram + Gemini)

Untuk mengaktifkan notifikasi alert dan analisis AI otomatis, ikuti langkah berikut:

#### A. Buat Telegram Bot
1. Buka Telegram, cari **@BotFather**.
2. Kirim `/newbot` dan ikuti instruksi.
3. Salin **token bot** yang diberikan (format: `123456789:AAHxxx...`).
4. Masukkan token ke `rpa-bot/.env` sebagai `TELEGRAM_BOT_TOKEN`.

#### B. Dapatkan Chat ID
1. Buka Telegram, cari **@userinfobot**.
2. Kirim pesan apa saja — bot akan membalas dengan **ID** akun Anda.
3. Masukkan ID tersebut ke `rpa-bot/.env` sebagai `TELEGRAM_CHAT_ID`.

#### C. Dapatkan Gemini API Key
1. Kunjungi [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey).
2. Klik **"Create API Key"** (gratis).
3. Masukkan ke `rpa-bot/.env` sebagai `GEMINI_API_KEY`.

#### D. Aktifkan Bot
Buka bot Telegram Anda (`@gas_rpa_anom_bot` atau nama bot yang Anda buat) dan tekan tombol **START**. Tanpa langkah ini, notifikasi tidak akan terkirim ke HP Anda.

#### Perintah Telegram yang Tersedia

| Perintah | Fungsi |
|----------|--------|
| `/status` | Lihat status semua proses PM2 secara real-time |
| `/restart` | Restart semua worker RPA bot |
| `/test` | Kirim pesan test untuk memastikan alert berfungsi |
| `/help` | Tampilkan daftar perintah |

---

## Alur Penggunaan (Panduan Step-by-Step)

Bagi pengguna (Agen/Pangkalan), berikut adalah langkah-langkah menggunakan sistem ini dari awal hingga akhir:

### 1. Menambahkan Akun Pangkalan
*   **Fungsi:** Menghubungkan aplikasi lokal dengan portal MyPertamina.
*   **Langkah:** Pergi ke menu **Akun Pangkalan**. Klik "Tambah Pangkalan" dan masukkan *username* serta *password* akun MyPertamina Anda. Data ini akan dienkripsi secara aman oleh sistem.

### 2. Memasukkan Data Pelanggan (Import & Auto-Sync)
*   **Fungsi:** Membangun *database* pelanggan yang berhak membeli gas.
*   **Langkah:** Pergi ke menu **Pelanggan**. Anda bisa menambah data satu per satu, atau menekan tombol **Import Excel** untuk memasukkan ratusan NIK sekaligus.
*   **Fitur Sync NIK:** Setelah data masuk, Anda bisa menekan tombol **Sync** (ikon panah melingkar) di tiap baris pelanggan. Sistem akan secara cerdas membedah NIK tersebut untuk otomatis mengisi form Tempat Lahir, Tanggal Lahir, Jenis Kelamin, Provinsi, hingga Kecamatan tanpa perlu diketik manual!

### 3. Membagi Jatah Transaksi (Distribusi)
*   **Fungsi:** Mengatur siapa saja yang membeli gas pada hari itu ke dalam sebuah "antrean draf" sebelum dilaporkan ke Pertamina.
*   **Langkah:** Buka menu **Transaksi Gas**. Centang kotak di samping nama-nama pelanggan yang membeli gas (bisa centang masal). Lalu klik tombol biru **Bagikan X Tabung**. Kini pelanggan tersebut masuk dalam daftar antrean transaksi berstatus *Belum Lapor*.

### 4. Menjalankan Bot RPA (Sinkronisasi)
*   **Fungsi:** Menyuruh robot (bot) untuk mulai mengetik dan menginput data antrean tersebut ke web asli MyPertamina.
*   **Langkah:** Di menu **Transaksi Gas**, pilih Pangkalan yang ingin Anda gunakan dari *dropdown*. Lalu tekan tombol merah **Mulai Sinkronisasi RPA**.
*   **Cara Kerja:** Bot akan otomatis membuka *browser* siluman, *login*, memasukkan NIK, memilih pelanggan, dan mencatat transaksi ke MyPertamina satu per satu secara otomatis.

### 5. Memantau Hasil Laporan (Live Tracking)
*   **Fungsi:** Melihat laporan sukses/tidaknya input data dari bot secara seketika (*real-time*).
*   **Langkah:** Anda cukup duduk diam memantau tabel di menu **Transaksi Gas**. Tabel ini sudah diatur cerdas; begitu bot berhasil/gagal memproses satu NIK, status di layar Anda akan langsung berubah secara ajaib (*Sukses* / *Gagal* / *Kuota Habis*), dan datanya akan langsung melompat ke urutan teratas beserta jam pemrosesan riil dari bot.

---

## Lisensi

Proyek ini menggunakan lisensi **MIT License**. Anda bebas menggunakan, memodifikasi, dan mendistribusikannya kembali.
