<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Welcome Header (Mobile only) -->
      <div class="md:hidden flex flex-col space-y-1">
        <h2 class="text-xl font-bold text-slate-900">Dashboard</h2>
        <p class="text-xs text-slate-500 font-mono">{{ currentDate }}</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Pelanggan Aktif -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-emerald-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pelanggan Aktif</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.totalPelanggan }}</h2>
          <p class="text-xs text-slate-500 mt-2">Pelanggan bersubsidi terdaftar</p>
        </div>

        <!-- Card 2: Tabung Terjual -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-indigo-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tabung Terjual Bulan Ini</span>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.tabungBulanIni }} <span class="text-sm font-semibold text-slate-400">tabung</span></h2>
          <p class="text-xs text-slate-500 mt-2">Hari ini: {{ stats.transaksiHariIni }} transaksi</p>
        </div>

        <!-- Card 3: NIK Kuota Habis -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-orange-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">NIK Kuota Habis</span>
            <div class="w-10 h-10 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.kuotaHabis }} <span class="text-sm font-semibold text-slate-400">NIK</span></h2>
          <p class="text-xs text-slate-500 mt-2">Sudah capai 4 tabung bulan ini</p>
        </div>

        <!-- Card 4: Belum Lapor -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-amber-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Belum Lapor ke MAP</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.belumDilaporkan }} <span class="text-sm font-semibold text-slate-400">transaksi</span></h2>
          <p class="text-xs text-slate-500 mt-2">Perlu disinkronkan ke MyPertamina</p>
        </div>
      </div>

      <!-- Live Monitoring RPA Sync -->
      <div v-if="liveData" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm relative overflow-hidden">
        <div v-if="liveData.is_running" class="absolute inset-x-0 top-0 h-[2.5px] bg-gradient-to-r from-indigo-500 via-violet-500 to-rose-500 animate-pulse"></div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div class="flex items-center space-x-3">
            <span class="relative flex h-3 w-3">
              <span :class="[
                'absolute inline-flex h-full w-full rounded-full opacity-75',
                liveData.is_running ? 'animate-ping bg-indigo-400' : 'bg-slate-400'
              ]"></span>
              <span :class="[
                'relative inline-flex rounded-full h-3 w-3',
                liveData.is_running ? 'bg-indigo-500' : 'bg-slate-500'
              ]"></span>
            </span>
            <div>
              <h3 class="font-bold text-slate-800 text-base">Live Monitoring RPA Bot</h3>
              <p class="text-xs text-slate-400 font-medium">Status sinkronisasi real-time via WebSockets</p>
            </div>
          </div>

          <div v-if="liveData.is_running" class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-[10px] text-indigo-650 uppercase tracking-widest font-black">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-550 animate-pulse"></span>
            <span>BOT AKTIF</span>
          </div>
          <div v-else class="flex items-center space-x-2">
            <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-[10px] text-slate-500 uppercase tracking-widest font-black">
              <span>BOT IDLE</span>
            </div>
            <button
              @click="liveData = null"
              class="w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-all duration-150"
              title="Tutup monitoring"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Terminal / Logs -->
          <div class="bg-slate-900 rounded-xl p-4 font-mono text-xs space-y-2.5 shadow-inner text-slate-100">
            <div class="flex justify-between border-b border-slate-800 pb-2 text-[10px] uppercase font-bold tracking-wider text-slate-400">
              <span>Aktivitas Terakhir</span>
              <span class="text-indigo-400">LIVE logs</span>
            </div>
            <div class="space-y-1.5">
              <p class="text-slate-500">> Menginisiasi engine puppeteer...</p>
              <p v-if="liveData?.pangkalan_name" class="text-slate-300">
                <span class="text-amber-400">[Akun]</span> Login ke: <strong class="text-white">{{ liveData.pangkalan_name }}</strong> ({{ liveData.pangkalan_index }}/{{ liveData.pangkalan_total }})
              </p>
              <p v-if="liveData?.nik_current" class="text-slate-400">
                <span class="text-emerald-400">[NIK]</span> Memproses NIK: <strong class="text-white">{{ liveData.nik_current }}</strong>
              </p>
              <p :class="[
                'font-bold',
                liveData.is_running ? 'text-indigo-300 animate-pulse' : 'text-slate-500'
              ]">
                &raquo; {{ liveData.step_label || 'Menunggu antrean...' }}
              </p>
            </div>
          </div>

          <!-- Progress Bars -->
          <div class="space-y-5 flex flex-col justify-center">
            <!-- Pangkalan Progress -->
            <div>
              <div class="flex justify-between text-xs font-semibold mb-2">
                <span class="text-slate-500">Progress Akun Pangkalan</span>
                <span class="text-slate-800 font-bold">{{ liveData.pangkalan_index }} / {{ liveData.pangkalan_total }}</span>
              </div>
              <div class="h-3 w-full bg-slate-100 border border-slate-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-500"
                  :style="{ width: `${(liveData.pangkalan_index / liveData.pangkalan_total) * 100}%` }"
                ></div>
              </div>
            </div>

            <!-- NIK Progress -->
            <div>
              <div class="flex justify-between text-xs font-semibold mb-2">
                <span class="text-slate-500">Progress Input NIK Transaksi</span>
                <span class="text-slate-800 font-bold">
                  {{ liveData.is_running && liveData.nik_current ? 'Aktif' : 'Menunggu' }}
                  ({{ liveData.nik_total }} Antrean)
                </span>
              </div>
              <div class="h-3 w-full bg-slate-100 border border-slate-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
                  :style="{ width: liveData.is_running ? '75%' : '0%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sisa Kuota Widget -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="mb-6">
          <h3 class="font-bold text-slate-800 text-base">Monitoring Sisa Kuota NIK</h3>
          <p class="text-xs text-slate-500 font-medium mb-4">Maksimal 4 tabung per pelanggan per bulan. Reset otomatis setiap bulan baru.</p>

          <!-- Filters -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <!-- NIK Search -->
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </span>
              <input
                v-model="filters.search"
                @input="debouncedSearch"
                type="text"
                placeholder="Cari NIK pelanggan..."
                class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
              />
            </div>

            <!-- Sisa Kuota Dropdown -->
            <div>
              <select
                v-model="filters.sisa_kuota"
                @change="applyFilters"
                class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-indigo-500 transition-all duration-200"
              >
                <option value="">Semua Sisa Kuota</option>
                <option value="4">Sisa 4 Tabung (Belum ada transaksi)</option>
                <option value="3">Sisa 3 Tabung</option>
                <option value="2">Sisa 2 Tabung</option>
                <option value="1">Sisa 1 Tabung</option>
                <option value="0">Sisa 0 Tabung (Kuota Habis)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200/80 text-xs text-slate-400 uppercase font-bold tracking-wider">
                <th class="py-3 px-4">NIK Pelanggan</th>
                <th class="py-3 px-4 text-center">Total Terpakai</th>
                <th class="py-3 px-4 text-center">Sisa Kuota</th>
                <th class="py-3 px-4">Bilah Progress</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr v-for="item in customersQuota.data" :key="item.id" class="hover:bg-slate-50/70 transition-all duration-150">
                <!-- NIK -->
                <td class="py-3.5 px-4 font-mono text-slate-700 tracking-wider">
                  {{ item.nik }}
                </td>
                <!-- Total Terpakai -->
                <td class="py-3.5 px-4 text-center">
                  <span :class="[
                    'px-2.5 py-1 rounded-lg text-xs font-bold inline-block border',
                    item.total_tabung_bulan_ini >= 4 ? 'bg-red-50 text-red-700 border-red-200' :
                    item.total_tabung_bulan_ini >= 2 ? 'bg-orange-50 text-orange-700 border-orange-200' :
                    'bg-emerald-50 text-emerald-700 border-emerald-200'
                  ]">
                    {{ item.total_tabung_bulan_ini }} / 4 tabung
                  </span>
                </td>
                <!-- Sisa Kuota -->
                <td class="py-3.5 px-4 text-center">
                  <span :class="[
                    'px-2.5 py-1 rounded-lg text-xs font-bold inline-block border',
                    item.sisa_kuota === 0 ? 'bg-red-50 text-red-655 border-red-200' :
                    item.sisa_kuota === 1 ? 'bg-orange-50 text-orange-655 border-orange-200' :
                    'bg-emerald-50 text-emerald-655 border-emerald-200'
                  ]">
                    {{ item.sisa_kuota }} tabung
                  </span>
                </td>
                <!-- Progress Visual -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center space-x-3">
                    <span class="font-mono text-xs text-slate-400 font-bold tracking-widest">
                      {{ '█'.repeat(Math.min(4, item.total_tabung_bulan_ini)) }}{{ '░'.repeat(Math.max(0, 4 - item.total_tabung_bulan_ini)) }}
                    </span>
                    <span class="text-xs text-slate-450 font-medium">({{ item.total_tabung_bulan_ini }}/4)</span>
                  </div>
                </td>
              </tr>
              <tr v-if="customersQuota.data.length === 0">
                <td colspan="4" class="py-8 text-center text-xs text-slate-400 uppercase tracking-widest font-semibold">
                  Tidak ada data kuota pelanggan aktif
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls (Full Windowed) -->
        <div v-if="customersQuota.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6 pt-4 border-t border-slate-100">
          <p class="text-xs text-slate-500 font-medium order-2 sm:order-1">
            Halaman <span class="font-bold text-slate-700">{{ customersQuota.current_page }}</span> dari {{ customersQuota.last_page }}
            <span class="text-slate-300 mx-1">·</span>
            {{ customersQuota.total }} pelanggan
          </p>
          <nav class="flex items-center gap-1 order-1 sm:order-2" aria-label="Pagination">
            <template v-for="item in quotaPages" :key="item">
              <span
                v-if="item === '...'"
                class="inline-flex items-end justify-center h-8 w-5 text-sm text-slate-400 pb-0.5"
              >···</span>
              <Link
                v-else
                :href="route('dashboard', { ...dashboardFilters, page: item })"
                :class="[
                  'inline-flex items-center justify-center h-8 w-8 rounded-md text-xs font-medium border transition-all duration-150',
                  item === customersQuota.current_page
                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm shadow-indigo-100 scale-105'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600 shadow-sm'
                ]"
              >{{ item }}</Link>
            </template>
          </nav>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

const page = usePage();
const stats = computed(() => page.props.stats);
const customersQuota = computed(() => page.props.customersQuota);

const filters = reactive({
  search: page.props.filters?.search || '',
  sisa_kuota: page.props.filters?.sisa_kuota || '',
});

const dashboardFilters = computed(() => ({
  search: filters.search,
  sisa_kuota: filters.sisa_kuota,
}));

// Full windowed pagination — always shows 5 pages in the window
const quotaPages = computed(() => {
  const current = customersQuota.value.current_page;
  const last = customersQuota.value.last_page;
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
  if (current <= 4) return [1, 2, 3, 4, 5, '...', last];
  if (current >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last];
  return [1, '...', current - 2, current - 1, current, current + 1, current + 2, '...', last];
});

let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 400);
};

const applyFilters = () => {
  router.get(route('dashboard'), {
    search: filters.search,
    sisa_kuota: filters.sisa_kuota,
  }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
};

const liveData = ref(page.props.auditProgress);
const authUserId = page.props.auth.user?.id;

const currentDate = computed(() => {
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return new Date().toLocaleDateString('id-ID', options);
});

onMounted(() => {
  if (window.Echo && authUserId) {
    window.Echo.private(`audit-progress.${authUserId}`)
      .listen('.AuditProgressUpdated', (data) => {
        const isRunning = data.is_running ?? false;
        if (!isRunning && data.finished_at) {
          const finishedAt = new Date(data.finished_at);
          const diffInMinutes = (new Date() - finishedAt) / 1000 / 60;
          if (diffInMinutes >= 10) {
            liveData.value = null;
            return;
          }
        }
        liveData.value = data;
      });
  }
});

onUnmounted(() => {
  if (window.Echo && authUserId) {
    window.Echo.leave(`audit-progress.${authUserId}`);
  }
});
</script>

<style scoped>
.text-indigo-650 {
  color: rgb(67, 56, 202);
}
.bg-indigo-550 {
  background-color: rgb(79, 70, 229);
}
.text-slate-450 {
  color: rgb(100, 116, 139);
}
.text-red-655 {
  color: rgb(185, 28, 28);
}
.text-orange-655 {
  color: rgb(194, 65, 12);
}
.text-emerald-655 {
  color: rgb(4, 120, 87);
}
</style>
