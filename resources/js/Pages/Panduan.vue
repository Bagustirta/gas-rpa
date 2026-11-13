<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6 pb-10">
      <!-- Header -->
      <div class="bg-white border border-slate-300 rounded-2xl p-6 shadow-sm">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Panduan Penggunaan</h1>
        <p class="text-sm sm:text-base text-slate-600 mt-1">
          Urutan langkah pemakaian aplikasi mulai dari mengisi data pelanggan hingga lapor otomatis ke MyPertamina.
        </p>
      </div>

      <!-- Langkah 1 -->
      <div class="bg-white border border-slate-300 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
          <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white font-bold text-sm flex items-center justify-center shrink-0">1</span>
          <h2 class="text-lg font-bold text-slate-900">Persiapan Akun Pangkalan & Data Pelanggan</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2">
            <h3 class="font-bold text-slate-900 text-sm sm:text-base">A. Mengisi Akun Pangkalan</h3>
            <ol class="list-decimal pl-5 space-y-1.5 text-sm text-slate-700">
              <li>Buka menu <strong>Akun Pangkalan</strong> di sebelah kiri.</li>
              <li>Klik tombol <strong>+ Tambah Akun</strong>.</li>
              <li>Masukkan Nama Pangkalan, Nomor HP / Email, dan PIN MyPertamina Anda, lalu simpan.</li>
            </ol>
          </div>

          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2">
            <h3 class="font-bold text-slate-900 text-sm sm:text-base">B. Mengisi Data NIK Pelanggan</h3>
            <ol class="list-decimal pl-5 space-y-1.5 text-sm text-slate-700">
              <li>Buka menu <strong>Data Pelanggan</strong>.</li>
              <li>Klik <strong>+ Tambah NIK</strong> untuk input satu per satu, atau klik <strong>Import Excel</strong> untuk memasukkan banyak NIK sekaligus.</li>
              <li>Klik <strong>Sync Semua NIK</strong> bila ingin melengkapi data wilayah dan tanggal lahir otomatis dari 16 digit NIK.</li>
            </ol>
          </div>
        </div>
      </div>

      <!-- Langkah 2 -->
      <div class="bg-white border border-slate-300 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
          <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-bold text-sm flex items-center justify-center shrink-0">2</span>
          <h2 class="text-lg font-bold text-slate-900">Membagi Jatah Tabung (Distribusi Otomatis)</h2>
        </div>

        <ol class="list-decimal pl-5 space-y-2 text-sm sm:text-base text-slate-700">
          <li>Buka menu <strong>Transaksi Gas</strong>.</li>
          <li>Klik tombol hijau <strong>Distribusi Otomatis</strong> di bagian atas.</li>
          <li>Pilih <strong>Pangkalan</strong>, isi <strong>Total Tabung</strong> yang ingin dibagikan, dan tentukan tanggalnya.</li>
          <li>Sistem akan membagi tabung secara merata dimulai dari pelanggan yang belum pernah mendapat jatah bulan ini (<strong>0/4</strong>) terlebih dahulu sebelum lanjut ke pelanggan yang sudah pernah mendapat tabung (<strong>1/4, 2/4, 3/4</strong>).</li>
        </ol>

        <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 text-sm text-amber-950">
          <strong>Tips:</strong> Jika stok di MyPertamina hari ini ada 40 tabung, Anda bisa memasukkan jumlah distribusi lebih banyak (misalnya 60–80 tabung). Saat proses lapor berjalan, jika ada NIK yang kuotanya sudah habis di pangkalan lain, bot akan otomatis melewati NIK tersebut dan lanjut ke antrean berikutnya sampai stok 40 tabung di MyPertamina habis terpakai.
        </div>
      </div>

      <!-- Langkah 3 -->
      <div class="bg-white border border-slate-300 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
          <span class="w-8 h-8 rounded-lg bg-amber-600 text-white font-bold text-sm flex items-center justify-center shrink-0">3</span>
          <h2 class="text-lg font-bold text-slate-900">Melaporkan ke MyPertamina (Lapor Pertamina)</h2>
        </div>

        <ol class="list-decimal pl-5 space-y-2 text-sm sm:text-base text-slate-700">
          <li>Pastikan sudah ada daftar transaksi berstatus <strong>Belum Lapor</strong> pada tabel.</li>
          <li>Klik tombol oranye <strong>Lapor Pertamina</strong> di bagian atas halaman Transaksi Gas.</li>
          <li>Bot akan berjalan otomatis untuk login dan menginput NIK satu per satu ke web MyPertamina.</li>
          <li>Pantau hasilnya secara langsung pada kotak <strong>Progres Lapor Pertamina</strong> di bagian atas tabel:</li>
        </ol>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 pt-1">
          <div class="bg-emerald-50 border border-emerald-300 rounded-xl p-3 text-center">
            <span class="block text-xs font-bold uppercase text-emerald-800">Sukses</span>
            <span class="text-xs text-emerald-700 mt-1 block">Berhasil masuk ke Pertamina</span>
          </div>
          <div class="bg-blue-50 border border-blue-300 rounded-xl p-3 text-center">
            <span class="block text-xs font-bold uppercase text-blue-800">Limit</span>
            <span class="text-xs text-blue-700 mt-1 block">Jatah bulanan NIK sudah habis</span>
          </div>
          <div class="bg-amber-50 border border-amber-300 rounded-xl p-3 text-center">
            <span class="block text-xs font-bold uppercase text-amber-800">Antre</span>
            <span class="text-xs text-amber-700 mt-1 block">Menunggu giliran diproses</span>
          </div>
          <div class="bg-rose-50 border border-rose-300 rounded-xl p-3 text-center">
            <span class="block text-xs font-bold uppercase text-rose-800">Gagal</span>
            <span class="text-xs text-rose-700 mt-1 block">NIK kendala / tidak valid</span>
          </div>
          <div class="col-span-2 sm:col-span-1 bg-slate-100 border border-slate-300 rounded-xl p-3 text-center">
            <span class="block text-xs font-bold uppercase text-slate-700">Total</span>
            <span class="text-xs text-slate-600 mt-1 block">Jumlah seluruh antrean</span>
          </div>
        </div>
      </div>

      <!-- Langkah 4 -->
      <div class="bg-white border border-slate-300 rounded-2xl p-6 shadow-sm space-y-3">
        <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
          <span class="w-8 h-8 rounded-lg bg-sky-600 text-white font-bold text-sm flex items-center justify-center shrink-0">4</span>
          <h2 class="text-lg font-bold text-slate-900">Mengecek Sisa Kuota Bulanan (Cek Kuota)</h2>
        </div>

        <p class="text-sm sm:text-base text-slate-700">
          Jika Anda ingin mencocokkan sisa jatah bulanan pelanggan langsung dengan data di MyPertamina tanpa melakukan input penjualan, klik tombol biru <strong>Cek Kuota</strong> di menu Transaksi Gas. Bot akan memeriksa sisa kuota tiap NIK di latar belakang.
        </p>
      </div>

      <!-- Langkah 5: Tanya Jawab -->
      <div class="bg-white border border-slate-300 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
          <span class="w-8 h-8 rounded-lg bg-rose-600 text-white font-bold text-sm flex items-center justify-center shrink-0">5</span>
          <h2 class="text-lg font-bold text-slate-900">Pertanyaan & Kendala Umum</h2>
        </div>

        <div class="space-y-3">
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <h4 class="font-bold text-slate-900 text-sm sm:text-base">
              Bagaimana jika proses bot terlihat berhenti atau macet lama?
            </h4>
            <p class="text-slate-700 text-sm mt-1">
              Klik tombol merah <strong>Batalkan Lapor</strong> (atau <strong>Batalkan Cek</strong>) di bagian atas halaman Transaksi Gas untuk menghentikan antrean yang macet, lalu Anda bisa klik <strong>Lapor Pertamina</strong> kembali untuk melanjutkan sisa antrean.
            </p>
          </div>

          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <h4 class="font-bold text-slate-900 text-sm sm:text-base">
              Apa yang terjadi jika stok tabung di web MyPertamina sudah 0 (habis)?
            </h4>
            <p class="text-slate-700 text-sm mt-1">
              Bot akan melakukan pengecekan ulang sebanyak 2 kali untuk memastikan stok memang benar-benar 0. Jika stok pangkalan tersebut sudah habis, sisa antrean di pangkalan itu akan otomatis dibersihkan dan bot langsung berpindah ke akun pangkalan berikutnya.
            </p>
          </div>

          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <h4 class="font-bold text-slate-900 text-sm sm:text-base">
              Kenapa ada pelanggan yang tidak ikut terbagi saat Distribusi Otomatis?
            </h4>
            <p class="text-slate-700 text-sm mt-1">
              Sistem otomatis melewati pelanggan yang statusnya Nonaktif, usianya belum genap 17 tahun, sudah mencapai batas maksimal 4 tabung per bulan, atau sudah mencapai batas pembelian mingguan.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
</script>
