# 🤖 Bot RPA Sinkronisasi LPG Pertamina

Skrip otomatisasi menggunakan **Node.js + Puppeteer** untuk membaca antrean transaksi dari aplikasi Laravel lokal Anda dan menginputkannya secara otomatis ke situs **MAP Pertamina**.

## 📋 Prasyarat
Sebelum menjalankan bot ini, pastikan komputer Anda sudah terpasang:
1. **Node.js** (Versi 18 ke atas) -> [Download Node.js](https://nodejs.org/)
2. **Google Chrome** (Direkomendasikan versi terbaru)

---

## 🛠️ Langkah Instalasi & Pengaturan

### 1. Masuk ke Folder Bot
Buka terminal/command prompt pada komputer Anda dan masuk ke folder bot ini:
```bash
cd d:\laragon\www\gas\rpa-bot
```

### 2. Instal Dependensi
Jalankan perintah berikut untuk mengunduh semua pustaka yang dibutuhkan (Puppeteer, Axios, Dotenv):
```bash
npm install
```

### 3. Konfigurasi File `.env`
Salin file `.env.example` menjadi `.env`:
```bash
copy .env.example .env
```
Buka file `.env` yang baru dibuat dengan text editor Anda dan sesuaikan isinya:
* `LARAVEL_APP_URL`: Alamat URL aplikasi Laravel Anda (contoh: `http://localhost:8000` atau `http://gas.test`).
* `LPG_API_KEY`: Harus sama dengan `LPG_API_KEY` yang ada di file `.env` Laravel Anda (default: `lpg-secret-token-12345`).
* `PERTAMINA_PORTAL_URL`: Halaman login MAP Pertamina.
* **Selectors (`SELECTOR_...`):** Elemen input NIK, tombol cari, input kuota, dan tombol simpan pada website Pertamina. *(Lihat panduan penyesuaian selector di bawah).*

---

## 🚀 Cara Menjalankan Bot

1. Di aplikasi Laravel Anda, masuk ke menu **Transaksi Gas** dan klik **"Mulai Sync Pertamina (RPA)"**. Ini akan membuat antrean perintah sinkronisasi.
2. Buka terminal Anda di folder `rpa-bot` ini dan jalankan perintah:
   ```bash
   npm start
   ```
3. Jendela browser Google Chrome otomatis akan terbuka dan menuju ke portal Pertamina.
4. **Login Secara Manual:** Lakukan login (masukkan username, password, dan Captcha) secara manual pada jendela Chrome yang terbuka tersebut. Navigasikan ke halaman **Input Transaksi** di web Pertamina.
5. **Jalankan Otomatisasi:** Kembali ke terminal Anda, lalu tekan tombol **[ENTER]**. 
6. Bot akan otomatis mengetikkan NIK, menekan tombol cari, mengisi jumlah tabung, menyimpan, dan melaporkannya kembali ke Laravel Anda hingga semua antrean selesai.

---

## 🔍 Menyesuaikan Selector HTML (Jika Web Pertamina Berubah)
Jika di kemudian hari tombol atau kolom input di website Pertamina tidak bisa diketik atau tidak bisa diklik oleh bot, Anda hanya perlu memperbarui selector HTML-nya di file `.env` bot:

1. Buka browser Chrome, buka website Pertamina.
2. Klik kanan pada kolom input NIK, pilih **Inspect** (Periksa).
3. Cari ID atau Class unik dari elemen tersebut (misal `id="nik"` atau `class="form-control-nik"`).
4. Update nilai selector di file `.env` bot Anda:
   ```env
   SELECTOR_NIK_INPUT=#nik-anda
   ```
