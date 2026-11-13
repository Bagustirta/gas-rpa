# RPA Bot

Worker Node.js + Puppeteer untuk sinkronisasi otomatis transaksi LPG 3 Kg dari aplikasi web ke portal MAP MyPertamina.

## File Utama

- `bot.js` — Worker Puppeteer untuk input transaksi ke MyPertamina.
- `check-quota.js` — Worker pengecekan sisa kuota bulanan pelanggan.
- `scaler.js` — Mengelola jumlah worker `rpa-bot` secara otomatis sesuai antrean.
- `watchdog.js` — Memantau error log dan mengirim notifikasi ke Telegram.

## Pengaturan

```bash
npm install
copy .env.example .env
```

Isi `LARAVEL_APP_URL` dan `LPG_API_KEY` di `.env` agar sesuai dengan konfigurasi Laravel.
