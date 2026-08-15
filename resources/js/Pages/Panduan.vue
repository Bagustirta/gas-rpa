<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-8 pb-12">
      <!-- Header Banner -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-cyan-500 via-blue-500 to-blue-700 p-8 sm:p-12 shadow-2xl">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-40 h-40 bg-cyan-300 opacity-20 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-6">
          <div class="p-4 bg-white/20 backdrop-blur-md text-white rounded-2xl shadow-inner border border-white/30 inline-flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight drop-shadow-sm">Buku Panduan Sistem</h1>
            <p class="text-cyan-100 mt-2 text-lg font-medium max-w-xl">Pelajari cara mengoptimalkan Sistem Manajemen & Sinkronisasi Gas RPA Pertamina untuk bisnis Anda.</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Sidebar / Navigation-like info -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Daftar Isi Singkat</h3>
            <ul class="space-y-3">
              <li class="flex items-center space-x-3 text-slate-600 font-medium">
                <div class="w-6 h-6 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-xs">1</div>
                <span>Tahap Persiapan Data</span>
              </li>
              <li class="flex items-center space-x-3 text-slate-600 font-medium">
                <div class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xs">2</div>
                <span>Mencatat Transaksi Otomatis</span>
              </li>
              <li class="flex items-center space-x-3 text-slate-600 font-medium">
                <div class="w-6 h-6 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-xs">3</div>
                <span>Menjalankan Bot RPA</span>
              </li>
              <li class="flex items-center space-x-3 text-slate-600 font-medium">
                <div class="w-6 h-6 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center text-xs">4</div>
                <span>Audit Kuota Pelanggan</span>
              </li>
              <li class="flex items-center space-x-3 text-slate-600 font-medium">
                <div class="w-6 h-6 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-xs">5</div>
                <span>Kendala & Solusi (FAQ)</span>
              </li>
            </ul>
          </div>
          
          <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-6 shadow-lg text-white">
            <div class="flex items-center space-x-3 mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="font-bold text-lg">Tips Cepat</h3>
            </div>
            <p class="text-slate-300 text-sm leading-relaxed">
              Anda tidak perlu selalu menunggui layar saat proses RPA berjalan. Setelah tombol diklik, biarkan sistem yang mengambil alih pekerjaannya untuk Anda.
            </p>
          </div>
        </div>

        <!-- Main Content (Steps) -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Step 1 -->
          <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 relative overflow-hidden group hover:border-blue-200 transition-colors">
            <div class="absolute -right-8 -top-8 text-slate-50 opacity-50 font-black text-9xl transition-transform group-hover:scale-110 duration-500 pointer-events-none">1</div>
            
            <div class="flex items-center space-x-4 mb-6 relative z-10">
              <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
              </div>
              <h2 class="text-2xl font-extrabold text-slate-800">Tahap Persiapan Data</h2>
            </div>
            
            <div class="relative z-10 space-y-6">
              <p class="text-slate-600">Sebelum bisa mulai melaporkan transaksi, Anda harus memastikan data master sudah terisi dengan benar.</p>
              
              <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                  Menambahkan Akun Pangkalan
                </h3>
                <ol class="list-decimal pl-5 space-y-2 text-sm text-slate-600">
                  <li>Buka menu <strong>Akun Pangkalan</strong> di sidebar kiri.</li>
                  <li>Klik tombol <strong>+ Tambah Akun</strong>.</li>
                  <li>Masukkan Nama Pangkalan, Email/No HP, dan Password MyPertamina Anda.</li>
                </ol>
              </div>

              <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                  Menambahkan Data Pelanggan (Import & Auto-Sync)
                </h3>
                <ol class="list-decimal pl-5 space-y-2 text-sm text-slate-600">
                  <li>Buka menu <strong>Pelanggan</strong>.</li>
                  <li>Untuk menambah secara manual, klik tombol <strong>+ Tambah Pelanggan</strong> lalu ketikkan 16 digit NIK.</li>
                  <li>Untuk memasukkan data masal sekaligus, klik tombol <strong>Import Excel</strong>. Sistem akan mendeteksi judul kolom (NIK, Status, Tempat Lahir, Tanggal Lahir) di file Anda secara otomatis.</li>
                  <li><strong>Fitur Sync NIK:</strong> Setelah NIK masuk, tekan tombol <strong>Sync</strong> (ikon panah melingkar) pada baris pelanggan. Sistem akan otomatis membedah NIK untuk mengisi Provinsi, Kabupaten, Kecamatan, Tanggal Lahir, dan Jenis Kelamin tanpa perlu diketik manual!</li>
                  <li>Anda juga dapat menekan tombol <strong>Export Excel</strong> jika sewaktu-waktu ingin mencadangkan (<em>backup</em>) data pelanggan Anda ke komputer.</li>
                </ol>
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 relative overflow-hidden group hover:border-emerald-200 transition-colors">
            <div class="absolute -right-8 -top-8 text-slate-50 opacity-50 font-black text-9xl transition-transform group-hover:scale-110 duration-500 pointer-events-none">2</div>
            
            <div class="flex items-center space-x-4 mb-6 relative z-10">
              <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h2 class="text-2xl font-extrabold text-slate-800">Mencatat Transaksi Otomatis</h2>
            </div>
            
            <div class="relative z-10">
              <p class="text-slate-600 mb-5">Karena mencatat pembeli satu per satu itu merepotkan, gunakan fitur otomatis sistem kami untuk membagikan jatah tabung ke warga dalam hitungan detik!</p>
              
              <ol class="space-y-4">
                <li class="flex items-start">
                  <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                  <p class="text-sm text-slate-700">Buka menu <strong>Transaksi Gas</strong>.</p>
                </li>
                <li class="flex items-start">
                  <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                  <p class="text-sm text-slate-700">Klik tombol <strong>Distribusi Otomatis</strong> (warna hijau) di pojok kanan atas layar.</p>
                </li>
                <li class="flex items-start">
                  <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                  <div class="text-sm text-slate-700">
                    <p>Pilih pangkalan, lalu masukkan <strong>Total Tabung</strong>.</p>
                    <p class="text-slate-800 font-medium mt-3 bg-amber-100/80 p-3.5 rounded-xl border border-amber-300 text-sm shadow-sm leading-relaxed">
                      💡 <strong class="text-amber-700">Tips Cerdas:</strong> Jika hari ini Anda mendapat jatah 40 tabung, Anda bisa memasukkan <strong>70-80 tabung</strong> di sistem! Tujuannya agar sistem mencatat lebih banyak antrean pelanggan untuk membantu menyeleksi secara otomatis mana status pelanggan yang masih aktif dan mana yang tidak saat proses sinkronisasi RPA nanti.
                    </p>
                  </div>
                </li>
                <li class="flex items-start">
                  <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">4</div>
                  <p class="text-sm text-slate-700">Sistem akan secara pintar membagi rata tabung tersebut hanya kepada warga yang kuota bulanannya belum habis!</p>
                </li>
              </ol>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 relative overflow-hidden group hover:border-amber-200 transition-colors">
            <div class="absolute -right-8 -top-8 text-slate-50 opacity-50 font-black text-9xl transition-transform group-hover:scale-110 duration-500 pointer-events-none">3</div>
            
            <div class="flex items-center space-x-4 mb-6 relative z-10">
              <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h2 class="text-2xl font-extrabold text-slate-800">Menjalankan Bot RPA</h2>
            </div>
            
            <div class="relative z-10">
              <p class="text-slate-600 mb-5">Ini adalah fungsi magis dari sistem. Bot akan bertindak layaknya manusia yang mengetik laporan ke web Pertamina secara otomatis.</p>
              
              <div class="bg-amber-50/50 rounded-2xl p-5 border border-amber-100/50 mb-6">
                <ol class="space-y-4">
                  <li class="flex items-start">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-white border border-amber-200 text-amber-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                    <p class="text-sm text-slate-700">Pastikan ada transaksi yang berstatus <code class="bg-amber-100 text-amber-800 font-bold px-2 py-1 rounded border border-amber-300 text-sm shadow-sm">Belum Lapor</code>.</p>
                  </li>
                  <li class="flex items-start">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-white border border-amber-200 text-amber-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                    <p class="text-sm text-slate-700">Klik tombol <strong>Sync (RPA)</strong> berwarna oranye di menu Transaksi Gas.</p>
                  </li>
                  <li class="flex items-start">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-white border border-amber-200 text-amber-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                    <p class="text-sm text-slate-700">Anda dapat memantau pergerakan bot melalui <strong>Kartu Progres RPA</strong> di bagian atas layar. Geser (<em>swipe</em>) kartu tersebut untuk melihat detail per pangkalan.</p>
                  </li>
                </ol>
              </div>

              <div class="bg-slate-900 rounded-2xl p-5 text-white">
                <h4 class="font-bold text-slate-100 mb-3 text-sm tracking-wider uppercase">Cara Membaca Indikator Progres</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                  <div class="bg-slate-800 p-3 rounded-xl border border-slate-700 text-center">
                    <span class="block text-indigo-400 font-black text-xl mb-1">T</span>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Total Antrean</span>
                  </div>
                  <div class="bg-slate-800 p-3 rounded-xl border border-slate-700 text-center">
                    <span class="block text-emerald-400 font-black text-xl mb-1">S</span>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Sukses Dilapor</span>
                  </div>
                  <div class="bg-slate-800 p-3 rounded-xl border border-slate-700 text-center">
                    <span class="block text-blue-400 font-black text-xl mb-1">L</span>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Limit / Habis</span>
                  </div>
                  <div class="bg-slate-800 p-3 rounded-xl border border-slate-700 text-center">
                    <span class="block text-rose-400 font-black text-xl mb-1">G</span>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Gagal Lapor</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 relative overflow-hidden group hover:border-purple-200 transition-colors">
            <div class="absolute -right-8 -top-8 text-slate-50 opacity-50 font-black text-9xl transition-transform group-hover:scale-110 duration-500 pointer-events-none">4</div>
            
            <div class="flex items-center space-x-4 mb-6 relative z-10">
              <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <h2 class="text-2xl font-extrabold text-slate-800">Audit Kuota Pelanggan</h2>
            </div>
            
            <div class="relative z-10">
              <p class="text-slate-600 mb-5">Anda juga bisa memeriksa secara mandiri sisa kuota tabung bulanan pelanggan tanpa harus mencatat transaksi di antrean terlebih dahulu.</p>
              
              <ol class="space-y-4">
                <li class="flex items-start">
                  <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                  <p class="text-sm text-slate-700">Buka menu <strong>Transaksi Gas</strong>.</p>
                </li>
                <li class="flex items-start">
                  <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                  <p class="text-sm text-slate-700">Centang kotak di samping nama pelanggan yang ingin dicek kuotanya pada tabel antrean.</p>
                </li>
                <li class="flex items-start">
                  <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                  <p class="text-sm text-slate-700">Tekan tombol <strong>Cek Kuota (Pertamina)</strong>. Sistem akan langsung menanyakan ke server MyPertamina secara <em>real-time</em> dan menampilkan sisa jatah bulan ini secara ajaib.</p>
                </li>
              </ol>
            </div>
          </div>

          <!-- FAQ Section -->
          <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center space-x-4 mb-8">
              <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h2 class="text-2xl font-extrabold text-slate-800">FAQ & Kendala</h2>
            </div>

            <div class="space-y-4">
              
              <!-- Accordion Item 1 -->
              <div class="border border-slate-200 rounded-2xl p-1 transition-all hover:border-slate-300">
                <div class="bg-slate-50 rounded-xl p-4 cursor-pointer">
                  <h4 class="font-bold text-slate-800 text-sm md:text-base flex items-start gap-3">
                    <span class="text-rose-500 flex-shrink-0 bg-rose-100 px-2 py-0.5 rounded-lg text-xs">Tanya</span> 
                    Apa yang harus dilakukan jika proses Bot macet lama?
                  </h4>
                  <p class="text-slate-600 text-sm mt-3 ml-12">
                    Jika server MyPertamina sangat lambat dan bot terlihat macet, klik tombol <strong>Reset Sync (Hard Reset)</strong> berwarna merah di bagian atas menu Transaksi Gas. Ini akan membatalkan antrean dengan aman. Anda bisa melanjutkannya lagi nanti.
                  </p>
                </div>
              </div>

              <!-- Accordion Item 2 -->
              <div class="border border-slate-200 rounded-2xl p-1 transition-all hover:border-slate-300">
                <div class="bg-slate-50 rounded-xl p-4 cursor-pointer">
                  <h4 class="font-bold text-slate-800 text-sm md:text-base flex items-start gap-3">
                    <span class="text-rose-500 flex-shrink-0 bg-rose-100 px-2 py-0.5 rounded-lg text-xs">Tanya</span> 
                    Di riwayat muncul error "Server Pertamina Error 500"?
                  </h4>
                  <p class="text-slate-600 text-sm mt-3 ml-12">
                    Itu berarti server pusat milik Pertamina sedang <em>down</em> atau kepenuhan. Ini bukan kerusakan pada sistem atau laptop Anda. Silakan bersabar dan tunggu 15-30 menit sebelum mencoba lapor (Sync) kembali.
                  </p>
                </div>
              </div>

              <!-- Accordion Item 3 -->
              <div class="border border-slate-200 rounded-2xl p-1 transition-all hover:border-slate-300">
                <div class="bg-slate-50 rounded-xl p-4 cursor-pointer">
                  <h4 class="font-bold text-slate-800 text-sm md:text-base flex items-start gap-3">
                    <span class="text-rose-500 flex-shrink-0 bg-rose-100 px-2 py-0.5 rounded-lg text-xs">Tanya</span> 
                    Mengapa ada transaksi Gagal dengan catatan "Timeout 60s"?
                  </h4>
                  <p class="text-slate-600 text-sm mt-3 ml-12">
                    Sistem mendeteksi bahwa website Pertamina tidak merespons (blank) selama lebih dari 1 menit. Bot RPA akan otomatis menyerah untuk transaksi tersebut agar antrean lainnya tidak terhambat. Silakan coba lagi nanti!
                  </p>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
</script>
