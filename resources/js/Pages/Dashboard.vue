<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Welcome Header (Mobile only) -->
      <div class="md:hidden flex flex-col space-y-1">
        <h2 class="text-xl font-bold text-slate-900">Dashboard</h2>
        <p class="text-xs text-slate-500 font-mono">{{ currentDate }}</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5">
        <!-- Card 1: Pelanggan Aktif -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-emerald-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pelanggan Aktif</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.totalPelanggan }}</h2>
          <p class="text-[11px] text-slate-500 mt-2">Subsidi terdaftar</p>
        </div>

        <!-- Card 2: Tabung Terjual -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-indigo-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tabung Terjual</span>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.tabungBulanIni }} <span class="text-sm font-semibold text-slate-400">tabung</span></h2>
          <p class="text-[11px] text-slate-500 mt-2">Bulan ini</p>
        </div>

        <!-- Card 3: NIK Kuota Habis -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-orange-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kuota Habis</span>
            <div class="w-10 h-10 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.kuotaHabis }} <span class="text-sm font-semibold text-slate-400">NIK</span></h2>
          <p class="text-[11px] text-slate-500 mt-2">Capai 4 tabung/bulan</p>
        </div>

        <!-- Card 4: Kuota Limit -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-red-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kuota Limit</span>
            <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.transaksiLimit }}</h2>
          <p class="text-[11px] text-slate-500 mt-2">Ditolak MyPertamina</p>
        </div>

        <!-- Card 5: Belum Lapor -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:shadow-md hover:border-slate-300 transition-all duration-300 shadow-sm">
          <div class="absolute -right-4 -bottom-4 text-amber-500/10 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
          </div>
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Belum Lapor</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.belumDilaporkan }} <span class="text-sm font-semibold text-slate-400">trx</span></h2>
          <p class="text-[11px] text-slate-500 mt-2">Perlu disinkronkan</p>
        </div>
      </div>

      <!-- Live Monitoring RPA Sync -->
      <LiveMonitoringWidget :initialData="page.props.auditProgress" />

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
              <tr v-for="item in localCustomersQuota" :key="item.id" class="hover:bg-slate-50/70 transition-all duration-150">
                <!-- NIK -->
                <td class="py-3.5 px-4 font-mono text-slate-700 tracking-wider">
                  <div class="flex items-center space-x-2">
                    <span>{{ revealedNikMap[item.id] ? item.nik : maskNik(item.nik) }}</span>
                    <button @click="toggleNikShow(item.id)" class="text-slate-400 hover:text-indigo-600 transition-colors p-0.5" :title="revealedNikMap[item.id] ? 'Sembunyikan NIK' : 'Tampilkan NIK'">
                      <svg v-if="revealedNikMap[item.id]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.88 9.88a3 3 0 104.24 4.24m-1.41-5.65L18.36 18.36M1 1l22 22" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
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
              <tr v-if="localCustomersQuota.length === 0">
                <td colspan="4" class="py-8 text-center text-xs text-slate-400 uppercase tracking-widest font-semibold">
                  Tidak ada data kuota pelanggan aktif
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Infinite Scroll Trigger -->
        <div ref="loadMoreTrigger" class="flex items-center justify-center py-6 mt-4">
          <div v-if="customersQuota.next_page_url" class="flex items-center space-x-2 text-slate-500">
            <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium">Memuat data selanjutnya...</span>
          </div>
          <div v-else-if="localCustomersQuota.length > 0" class="text-xs font-medium text-slate-400 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
            Semua {{ customersQuota.total }} pelanggan telah dimuat
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import LiveMonitoringWidget from '../Components/LiveMonitoringWidget.vue';

const page = usePage();
const stats = computed(() => page.props.stats);
const customersQuota = computed(() => page.props.customersQuota);

const revealedNikMap = ref({});
const toggleNikShow = (id) => {
  revealedNikMap.value[id] = !revealedNikMap.value[id];
};

const maskNik = (nik) => {
  if (!nik || nik.length < 12) return nik || '-';
  return nik.substring(0, 6) + '******' + nik.substring(12);
};

const filters = reactive({
  search: page.props.filters?.search || '',
  sisa_kuota: page.props.filters?.sisa_kuota || '',
});

const dashboardFilters = computed(() => ({
  search: filters.search,
  sisa_kuota: filters.sisa_kuota,
}));

// Infinite Scroll Logic
const localCustomersQuota = ref([...(customersQuota.value?.data || [])]);
const loadMoreTrigger = ref(null);
const isLoadingMore = ref(false);
let observer = null;

watch(() => customersQuota.value, (newVal) => {
  if (!newVal) return;
  if (newVal.current_page === 1) {
    localCustomersQuota.value = [...newVal.data];
  } else {
    localCustomersQuota.value = [...localCustomersQuota.value, ...newVal.data];
  }
  isLoadingMore.value = false;
}, { deep: true });

const loadMore = () => {
  if (customersQuota.value.next_page_url && !isLoadingMore.value) {
    isLoadingMore.value = true;
    router.get(customersQuota.value.next_page_url, {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['customersQuota'],
    });
  }
};

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

const currentDate = computed(() => {
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return new Date().toLocaleDateString('id-ID', options);
});

const authUserId = computed(() => page.props.auth?.user?.id);

onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      loadMore();
    }
  }, { rootMargin: '300px' });
  
  if (loadMoreTrigger.value) {
    observer.observe(loadMoreTrigger.value);
  }

  if (window.Echo && authUserId.value) {
    window.Echo.private(`data-gas.${authUserId.value}`)
      .listen('DataGasUpdated', (e) => {
        router.reload({ only: ['stats', 'customersQuota'], preserveScroll: true });
      });
  }
});

onUnmounted(() => {
  if (observer) observer.disconnect();
  
  if (window.Echo && authUserId.value) {
    window.Echo.private(`data-gas.${authUserId.value}`).stopListening('DataGasUpdated');
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
