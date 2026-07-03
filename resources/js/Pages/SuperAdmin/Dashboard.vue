<template>
  <AppLayout>
    <Head title="Monitor Server & Aplikasi" />

    <div class="space-y-6">
      <!-- Header Section -->
      <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Monitor Server & Aplikasi</h2>
        <p class="text-sm text-slate-500 mt-1">Ringkasan lalu lintas dan statistik penggunaan keseluruhan sistem.</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Agen -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/60 relative overflow-hidden group">
          <div class="absolute -right-4 -bottom-4 opacity-5 text-indigo-500 group-hover:scale-110 transition-transform">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
          </div>
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Agen Aktif</p>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-black text-slate-800">{{ stats.totalUsers }}</span>
            <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">Akun</span>
          </div>
        </div>

        <!-- Pangkalan -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/60 relative overflow-hidden group">
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pangkalan</p>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-black text-slate-800">{{ stats.totalPangkalan }}</span>
            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Titik</span>
          </div>
        </div>

        <!-- Pelanggan -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/60 relative overflow-hidden group">
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Global Pelanggan</p>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-black text-slate-800">{{ stats.totalPelanggan }}</span>
            <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Warga</span>
          </div>
        </div>

        <!-- Transaksi Hari Ini -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/60 relative overflow-hidden group">
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Traffic Transaksi (Harian)</p>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-black text-slate-800">{{ stats.totalTransaksiHariIni }}</span>
            <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Req/Hari</span>
          </div>
        </div>

        <!-- Transaksi Bulan Ini -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/60 relative overflow-hidden group">
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Traffic Transaksi (Bulanan)</p>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-black text-slate-800">{{ stats.totalTransaksiBulanIni }}</span>
            <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Bulan Ini</span>
          </div>
        </div>
      </div>

      <!-- Bottom Section: 2 Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Server Monitoring -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-slate-900 rounded-2xl shadow-xl overflow-hidden border border-slate-700">
            <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center">
              <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                Kesehatan Server
              </h3>
            </div>
            <div class="p-6 space-y-6">
              
              <div class="flex flex-col">
                <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Server Status</span>
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  <span class="text-white font-mono text-base">{{ serverStatus.status }}</span>
                </div>
              </div>

              <div class="flex flex-col">
                <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">System Architecture</span>
                <span class="text-slate-300 font-mono text-sm">{{ serverStatus.os }}</span>
                <span class="text-slate-400 font-mono text-xs mt-1">PHP {{ serverStatus.php_version }} / Lrv {{ serverStatus.laravel_version }}</span>
              </div>

              <div class="flex flex-col">
                <div class="flex justify-between items-end mb-1">
                  <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Memory Allocation</span>
                  <span class="text-emerald-400 font-mono text-xs">{{ serverStatus.memory_usage }} MB</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2">
                  <div class="bg-emerald-500 h-2 rounded-full" :style="`width: ${Math.min((serverStatus.memory_usage / 128) * 100, 100)}%`"></div>
                </div>
                <span class="text-slate-500 text-[10px] mt-1 text-right">Max Limit 128 MB</span>
              </div>

            </div>
          </div>

          <!-- Bot Stats Card -->
          <div class="bg-indigo-900 rounded-2xl shadow-xl overflow-hidden border border-indigo-700">
            <div class="px-6 py-4 border-b border-indigo-800 flex justify-between items-center">
              <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Engine RPA Bot
              </h3>
            </div>
            <div class="p-6 flex flex-col gap-4">
              <div class="flex justify-between items-center bg-indigo-950/50 p-3 rounded-xl border border-indigo-800/50">
                <span class="text-indigo-200 text-xs font-semibold">Bot Sedang Berjalan</span>
                <span class="text-2xl font-black text-white">{{ serverStatus.active_bots }}</span>
              </div>
              <div class="flex justify-between items-center bg-indigo-950/50 p-3 rounded-xl border border-indigo-800/50">
                <span class="text-indigo-200 text-xs font-semibold">Job Sinkronisasi Hari Ini</span>
                <span class="text-2xl font-black text-white">{{ serverStatus.today_rpa_jobs }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Recent Activity Log -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden h-full flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
              <div>
                <h3 class="text-base font-bold text-slate-800">Log Transaksi Global (Live Feed)</h3>
                <p class="text-xs text-slate-500 mt-1">Menampilkan 8 transaksi terbaru dari seluruh agen.</p>
              </div>
            </div>
            
            <div class="flex-1 overflow-x-auto p-4">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-slate-200/60">
                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pelanggan</th>
                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Agen / Pangkalan</th>
                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Status</th>
                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Waktu</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-if="recentTransactions.length === 0">
                    <td colspan="4" class="py-8 text-center text-slate-400 text-sm">
                      Belum ada transaksi di seluruh sistem.
                    </td>
                  </tr>
                  <tr v-for="tx in recentTransactions" :key="tx.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="py-3 px-4">
                      <div class="font-semibold text-sm text-slate-700">{{ tx.nama_pelanggan }}</div>
                      <div class="text-xs font-mono text-slate-500">{{ tx.nik }}</div>
                    </td>
                    <td class="py-3 px-4">
                      <div class="font-semibold text-xs text-slate-700">{{ tx.agen }}</div>
                      <div class="text-[10px] font-medium text-indigo-600 bg-indigo-50 inline-block px-1.5 py-0.5 rounded mt-0.5">{{ tx.pangkalan }}</div>
                    </td>
                    <td class="py-3 px-4 text-center">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest border"
                        :class="{
                          'bg-yellow-50 text-yellow-600 border-yellow-200': tx.status === 'belum_lapor',
                          'bg-blue-50 text-blue-600 border-blue-200': tx.status === 'proses',
                          'bg-emerald-50 text-emerald-600 border-emerald-200': tx.status === 'sukses',
                          'bg-red-50 text-red-600 border-red-200': tx.status === 'gagal',
                          'bg-purple-50 text-purple-600 border-purple-200': tx.status === 'kuota_habis'
                        }">
                        <template v-if="tx.status === 'belum_lapor'">Antre</template>
                        <template v-else-if="tx.status === 'proses'">Proses</template>
                        <template v-else-if="tx.status === 'sukses'">Sukses</template>
                        <template v-else-if="tx.status === 'gagal'">Gagal</template>
                        <template v-else-if="tx.status === 'kuota_habis'">Limit</template>
                      </span>
                    </td>
                    <td class="py-3 px-4 text-right text-xs text-slate-400 font-medium whitespace-nowrap">
                      {{ tx.waktu }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
      
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
  stats: Object,
  serverStatus: Object,
  recentTransactions: Array,
});
</script>
