<template>
  <AppLayout>
    <Head title="Langganan & Pembayaran" />

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight sm:text-4xl">
          Pilih Paket Berlangganan Anda
        </h2>
        <p class="mt-4 text-lg text-slate-500 max-w-2xl mx-auto">
          Tingkatkan efisiensi pangkalan gas Anda dengan fitur otomatisasi sinkronisasi data pelanggan dan laporan ke sistem pusat.
        </p>
      </div>

      <!-- Pricing Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12 max-w-3xl mx-auto">
        <!-- Paket 1 Bulan -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden flex flex-col relative transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl">
          <div class="px-8 py-10 bg-gradient-to-b from-indigo-50 to-white flex-1 flex flex-col justify-between">
            <div>
              <h3 class="text-2xl font-bold text-slate-900 text-center mb-2">Paket 1 Bulan</h3>
              <div class="flex justify-center items-baseline my-6">
                <span class="text-4xl font-extrabold text-indigo-600 tracking-tight">Rp75.000</span>
                <span class="text-xl font-medium text-slate-500 ml-1">/bln</span>
              </div>
              <ul class="mt-8 space-y-4">
                <li class="flex items-start">
                  <svg class="h-6 w-6 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span class="ml-3 text-slate-600">Akses Penuh Bot RPA</span>
                </li>
                <li class="flex items-start">
                  <svg class="h-6 w-6 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span class="ml-3 text-slate-600">Sinkronisasi Pelanggan Otomatis</span>
                </li>
                <li class="flex items-start">
                  <svg class="h-6 w-6 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span class="ml-3 text-slate-600">Tanpa Batas Kuota NIK</span>
                </li>
              </ul>
            </div>
            <div class="mt-8">
              <a :href="getWaUrl('Paket 1 Bulan - Rp75.000')" 
                 target="_blank" 
                 class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all">
                <span>Pilih Paket 1 Bulan</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </a>
            </div>
          </div>
        </div>

        <!-- Paket 1 Tahun -->
        <div class="bg-indigo-600 rounded-3xl shadow-xl border border-indigo-700 overflow-hidden flex flex-col relative transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl">
          <div class="absolute top-0 right-0 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider">
            Lebih Hemat
          </div>
          <div class="px-8 py-10 bg-gradient-to-b from-indigo-600 to-indigo-800 flex-1 flex flex-col justify-between">
            <div>
              <h3 class="text-2xl font-bold text-white text-center mb-2">Paket 1 Tahun</h3>
              <div class="flex justify-center items-baseline my-6">
                <span class="text-4xl font-extrabold text-white tracking-tight">Rp750.000</span>
                <span class="text-xl font-medium text-indigo-200 ml-1">/thn</span>
              </div>
              <ul class="mt-8 space-y-4">
                <li class="flex items-start">
                  <svg class="h-6 w-6 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span class="ml-3 text-indigo-50">Semua Fitur Paket Bulanan</span>
                </li>
                <li class="flex items-start">
                  <svg class="h-6 w-6 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span class="ml-3 text-indigo-50">Gratis 2 Bulan Berlangganan</span>
                </li>
                <li class="flex items-start">
                  <svg class="h-6 w-6 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span class="ml-3 text-indigo-50">Prioritas Dukungan Teknis</span>
                </li>
              </ul>
            </div>
            <div class="mt-8">
              <a :href="getWaUrl('Paket 1 Tahun - Rp750.000')" 
                 target="_blank" 
                 class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl bg-yellow-400 hover:bg-yellow-300 text-yellow-950 font-bold text-sm shadow-md transition-all">
                <span>Pilih Paket 1 Tahun</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Cara Pembayaran -->
      <div class="bg-white rounded-3xl shadow-md border border-slate-200 overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-8 py-6">
          <h3 class="text-xl font-bold text-slate-900">Cara Melakukan Pembayaran</h3>
        </div>
        <div class="px-8 py-8">
          <div>
            <ol class="list-decimal list-inside space-y-4 text-slate-600">
              <li>Pilih salah satu paket berlangganan di atas.</li>
              <li>Lakukan transfer sesuai nominal paket ke rekening Bank Mandiri berikut:
                <div class="mt-3 p-4 bg-slate-100 rounded-xl border border-slate-200 w-full sm:w-fit flex flex-col sm:flex-row sm:items-center gap-4">
                  <div class="h-10 w-28 shrink-0 flex items-center justify-center overflow-hidden mix-blend-multiply">
                    <img src="/mandiri.png?v=4" alt="Mandiri Logo" class="w-full h-full object-cover object-center scale-110" />
                  </div>
                  <div class="border-t sm:border-t-0 sm:border-l border-slate-300 pt-3 sm:pt-0 sm:pl-4">
                    <p class="font-bold text-slate-800 text-lg sm:text-xl tracking-wider whitespace-nowrap">1750005076228</p>
                    <p class="text-sm text-slate-500">a/n I Putu Bagus Tirta</p>
                  </div>
                </div>
              </li>
              <li>Setelah berhasil melakukan pembayaran, silakan **klik kembali tombol Pilih Paket** pada paket berlangganan di atas.</li>
              <li>Anda akan otomatis diarahkan untuk menyelesaikan proses berlangganan dan aktivasi akun.</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();

const getWaUrl = (paketName = null) => {
  const email = page.props.auth.user?.email || '';
  const name = page.props.auth.user?.name || '';
  let msg = '';
  
  if (paketName) {
    msg = `Halo Admin Gas RPA, saya telah memilih paket:\n${paketName}\n`;
  } else {
    msg = `Halo Admin Gas RPA, saya ingin mengkonfirmasi pembayaran langganan.\n`;
  }
  
  msg += `\nBerikut data akun saya:\n- Nama: ${name}\n- Email: ${email}\n\nMohon diproses untuk aktivasi akun. Terima kasih!`;
  
  return `https://wa.me/6281913842931?text=${encodeURIComponent(msg)}`;
};
</script>
