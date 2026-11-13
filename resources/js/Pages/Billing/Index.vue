<template>
  <AppLayout>
    <Head title="Langganan & Pembayaran" />

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-8">
      <div class="text-center">
        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">
          Pilih Paket Berlangganan
        </h2>
        <p class="mt-2 text-sm sm:text-base text-slate-600 max-w-2xl mx-auto">
          Pilih paket aktif untuk menggunakan layanan distribusi dan pelaporan otomatis ke MyPertamina.
        </p>
      </div>

      <!-- Pricing Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
        <!-- Paket 1 Bulan -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-300 p-6 sm:p-8 flex flex-col justify-between">
          <div>
            <h3 class="text-xl font-bold text-slate-900 text-center">Paket 1 Bulan</h3>
            <div class="flex justify-center items-baseline my-5">
              <span class="text-4xl font-extrabold text-indigo-600">Rp75.000</span>
              <span class="text-base font-semibold text-slate-500 ml-1">/bulan</span>
            </div>
            <ul class="mt-6 space-y-3 border-t border-slate-200 pt-5 text-sm sm:text-base">
              <li class="flex items-center">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="ml-3 text-slate-700 font-medium">Akses Penuh Bot Lapor Pertamina</span>
              </li>
              <li class="flex items-center">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="ml-3 text-slate-700 font-medium">Distribusi & Cek Kuota Otomatis</span>
              </li>
              <li class="flex items-center">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="ml-3 text-slate-700 font-medium">Tanpa Batas Jumlah NIK Pelanggan</span>
              </li>
            </ul>
          </div>
          <div class="mt-8">
            <a
              :href="getWaUrl('Paket 1 Bulan - Rp75.000')"
              target="_blank"
              class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-sm transition-colors"
            >
              <span>Pilih Paket 1 Bulan</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
          </div>
        </div>

        <!-- Paket 1 Tahun -->
        <div class="bg-indigo-600 rounded-2xl shadow-md border border-indigo-700 p-6 sm:p-8 flex flex-col justify-between relative overflow-hidden">
          <div class="absolute top-0 right-0 bg-amber-400 text-amber-950 text-xs font-bold px-3 py-1 rounded-bl-xl uppercase">
            Hemat 2 Bulan
          </div>
          <div>
            <h3 class="text-xl font-bold text-white text-center">Paket 1 Tahun</h3>
            <div class="flex justify-center items-baseline my-5">
              <span class="text-4xl font-extrabold text-white">Rp750.000</span>
              <span class="text-base font-semibold text-indigo-200 ml-1">/tahun</span>
            </div>
            <ul class="mt-6 space-y-3 border-t border-indigo-500 pt-5 text-sm sm:text-base">
              <li class="flex items-center">
                <svg class="h-5 w-5 text-emerald-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="ml-3 text-white font-medium">Semua Fitur Paket Bulanan</span>
              </li>
              <li class="flex items-center">
                <svg class="h-5 w-5 text-emerald-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="ml-3 text-white font-medium">Gratis 2 Bulan Berlangganan</span>
              </li>
              <li class="flex items-center">
                <svg class="h-5 w-5 text-emerald-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="ml-3 text-white font-medium">Prioritas Bantuan Teknis</span>
              </li>
            </ul>
          </div>
          <div class="mt-8">
            <a
              :href="getWaUrl('Paket 1 Tahun - Rp750.000')"
              target="_blank"
              class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl bg-amber-400 hover:bg-amber-300 text-amber-950 font-bold text-sm shadow-sm transition-colors"
            >
              <span>Pilih Paket 1 Tahun</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Cara Pembayaran -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-300 overflow-hidden max-w-3xl mx-auto">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <h3 class="text-lg font-bold text-slate-900">Cara Melakukan Pembayaran</h3>
        </div>
        <div class="p-6 sm:p-8">
          <ol class="list-decimal list-inside space-y-4 text-slate-700 text-sm sm:text-base">
            <li>Pilih salah satu paket berlangganan di atas.</li>
            <li>
              Lakukan transfer sesuai nominal paket ke rekening <strong>Bank Mandiri</strong> berikut:
              <div class="mt-3 p-4 bg-slate-50 rounded-xl border border-slate-300 w-full sm:w-fit flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="h-10 w-28 shrink-0 flex items-center justify-center overflow-hidden mix-blend-multiply">
                  <img src="/mandiri.png?v=4" alt="Mandiri Logo" class="w-full h-full object-cover object-center scale-110" />
                </div>
                <div class="border-t sm:border-t-0 sm:border-l border-slate-300 pt-3 sm:pt-0 sm:pl-4 flex flex-wrap items-center justify-between gap-4">
                  <div>
                    <p class="font-extrabold text-slate-900 text-lg sm:text-xl tracking-wider whitespace-nowrap">1750005076228</p>
                    <p class="text-sm font-medium text-slate-600">a/n I Putu Bagus Tirta</p>
                  </div>
                  <button
                    type="button"
                    @click="copyRekening"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors"
                    :class="copied ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-white hover:bg-slate-100 border-slate-300 text-slate-700'"
                  >
                    <svg v-if="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ copied ? 'Tersalin!' : 'Salin' }}</span>
                  </button>
                </div>
              </div>
            </li>
            <li>Setelah selesai transfer, klik tombol <strong>Pilih Paket</strong> pada paket yang Anda ambil di atas.</li>
            <li>Anda akan langsung diarahkan ke WhatsApp Admin untuk konfirmasi dan aktivasi akun.</li>
          </ol>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const copied = ref(false);

const copyRekening = async () => {
  try {
    await navigator.clipboard.writeText('1750005076228');
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2500);
  } catch (e) {}
};

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
