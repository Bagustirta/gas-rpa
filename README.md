# Gas RPA

Aplikasi manajemen distribusi LPG 3 Kg dan bot pelaporan otomatis ke portal MAP MyPertamina.

## Fitur Utama

- **Manajemen Pelanggan & Pangkalan:** Simpan data NIK pelanggan (terenkripsi) dan akun pangkalan MyPertamina, mendukung import/export Excel.
- **Distribusi Otomatis Merata:** Pembagian tabung bulanan secara bertahap (`0/4` diprioritaskan lebih dulu sebelum `1/4`, `2/4`, `3/4`) dengan validasi batas mingguan dan usia minimal 17 tahun.
- **Bot Pelaporan Otomatis (RPA):** Input transaksi ke web MyPertamina menggunakan Puppeteer, lengkap dengan verifikasi stok kosong dan penanganan kuota limit.
- **Monitoring & Notifikasi Telegram:** Pantau progres laporan langsung di web atau lewat bot Telegram (`/status`, `/restart`, `/test`).

## Kebutuhan Sistem

- PHP 8.2+ & Composer
- MySQL / MariaDB
- Node.js 18+ & PM2 (`npm install -g pm2`)

## Instalasi

### 1. Aplikasi Web (Laravel + Vue)

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Atur konfigurasi database dan `LPG_API_KEY` di file `.env`, lalu jalankan:

```bash
php artisan migrate
npm run build
php artisan serve
```

### 2. Bot RPA (`rpa-bot`)

```bash
cd rpa-bot
npm install
copy .env.example .env
```

Sesuaikan isi `rpa-bot/.env`:

```env
LARAVEL_APP_URL=http://localhost:8000
LPG_API_KEY=lpg-secret-token-12345

# Opsional: Notifikasi Telegram & Watchdog
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
GEMINI_API_KEY=
```

### 3. Menjalankan Layanan (PM2)

Dari folder utama project:

```bash
pm2 startOrRestart ecosystem.json
pm2 save
```

Atau jalankan file `start-server.bat` pada Windows.
