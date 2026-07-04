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
          <div class="px-8 py-10 bg-gradient-to-b from-indigo-50 to-white flex-1">
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
                <span class="ml-3 text-slate-600">Maksimal 2 Pangkalan</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Paket 1 Tahun -->
        <div class="bg-indigo-600 rounded-3xl shadow-xl border border-indigo-700 overflow-hidden flex flex-col relative transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl">
          <div class="absolute top-0 right-0 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider">
            Lebih Hemat
          </div>
          <div class="px-8 py-10 bg-gradient-to-b from-indigo-600 to-indigo-800 flex-1">
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
              <li>Lakukan transfer / kirim saldo DANA sesuai nominal paket ke nomor berikut:
                <div class="mt-3 p-4 bg-slate-100 rounded-xl border border-slate-200 w-full sm:w-fit flex flex-col sm:flex-row sm:items-center gap-4">
                  <div class="h-8 w-24 shrink-0 flex items-center">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg" alt="DANA Logo" class="h-full object-contain object-left sm:object-center" />
                  </div>
                  <div class="border-t sm:border-t-0 sm:border-l border-slate-300 pt-3 sm:pt-0 sm:pl-4">
                    <p class="font-bold text-slate-800 text-lg sm:text-xl tracking-wider whitespace-nowrap">0882-3576-2979</p>
                    <p class="text-sm text-slate-500">a/n I Ketut Anom Pathra</p>
                  </div>
                </div>
              </li>
              <li>Simpan bukti transfer (foto struk / screenshot m-banking).</li>
              <li>Klik tombol konfirmasi WhatsApp di bawah ini dan kirimkan bukti transfer Anda.</li>
              <li>Admin akan memvalidasi dan segera mengaktifkan akun Anda.</li>
            </ol>
          </div>
          
          <div class="mt-10 flex justify-center">
            <a :href="`https://wa.me/6281913842931?text=Halo%20Admin%20Gas%20RPA,%20saya%20ingin%20mengkonfirmasi%20pembayaran%20langganan%20untuk%20akun%20email:%20${$page.props.auth.user?.email}`" 
               target="_blank" 
               class="flex items-center justify-center space-x-3 w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-base sm:text-lg px-6 sm:px-12 py-3.5 sm:py-4 rounded-2xl shadow-lg transition-transform transform hover:scale-105">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 sm:h-8 sm:w-8 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.093 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
              </svg>
              <span class="whitespace-nowrap">Konfirmasi via WA</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
</script>
