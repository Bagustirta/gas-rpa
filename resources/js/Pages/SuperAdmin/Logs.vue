<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Audit Log Sistem</h2>
          <p class="text-sm text-slate-500 mt-1">Pantau seluruh aktivitas pengguna dan sistem secara real-time</p>
        </div>
        <div class="flex items-center space-x-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="search"
              @input="debouncedSearch"
              type="text"
              placeholder="Cari email, aksi, detail..."
              class="w-full sm:w-64 bg-white border border-slate-300 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors shadow-sm"
            />
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <th class="py-3 px-4 w-1/4">Pengguna</th>
                <th class="py-3 px-4 w-1/6">Aksi</th>
                <th class="py-3 px-4 w-1/3">Detail Aktivitas</th>
                <th class="py-3 px-4 w-1/4">Informasi Tambahan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold uppercase">
                      {{ log.user.charAt(0) }}
                    </div>
                    <div>
                      <div class="font-medium text-slate-900">{{ log.user.split(' (')[0] }}</div>
                      <div class="text-xs text-slate-500" v-if="log.user.includes('(')">{{ log.user.split('(')[1].replace(')', '') }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span :class="[
                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border',
                    getActionClass(log.action)
                  ]">
                    {{ log.action }}
                  </span>
                </td>
                <td class="py-3 px-4 text-slate-700">
                  {{ log.description }}
                </td>
                <td class="py-3 px-4">
                  <div class="text-xs text-slate-500 space-y-1">
                    <div class="flex items-center gap-1" title="Waktu">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {{ log.created_at }} ({{ log.time_ago }})
                    </div>
                    <div class="flex items-start gap-1" title="IP Address">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                      <div>
                        <div class="flex items-center gap-1.5">
                          <span>{{ log.ip_address || '-' }}</span>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-1">
                          <!-- Lacak Lokasi Button / Result -->
                          <div v-if="log.ip_address && log.ip_address !== '127.0.0.1'">
                            <button 
                              v-if="!logLocations[log.id] && !loadingLocation[log.id]"
                              @click="trackLocation(log.id, log.ip_address)"
                              class="text-[10px] text-indigo-600 hover:text-indigo-800 font-semibold underline decoration-indigo-300 decoration-dashed underline-offset-2 transition-colors inline-flex items-center"
                            >
                              Lacak Lokasi IP
                            </button>
                            
                            <div v-else-if="loadingLocation[log.id]" class="text-[10px] text-slate-400 flex items-center gap-1 animate-pulse">
                              <svg class="animate-spin h-3 w-3 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                              Mencari...
                            </div>
                            
                            <div v-else class="text-[10px] text-emerald-650 font-medium bg-emerald-50 border border-emerald-100 rounded px-1.5 py-0.5 inline-flex items-center gap-1">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                              {{ logLocations[log.id] }}
                            </div>
                          </div>
                          <div v-else-if="log.ip_address === '127.0.0.1'" class="text-[10px] text-slate-400">
                            (Server Lokal / Laragon)
                          </div>

                          <!-- Titik GPS Pasti (Jika ada) -->
                          <div v-if="log.latitude && log.longitude" class="flex items-center gap-1 text-[10px]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <a :href="`https://www.google.com/maps?q=${log.latitude},${log.longitude}`" target="_blank" class="text-rose-600 hover:text-rose-800 font-semibold underline decoration-rose-300 underline-offset-2 transition-colors inline-flex items-center">
                              Lihat Titik GPS Akurat
                            </a>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="logs.data.length === 0">
                <td colspan="4" class="py-8 text-center text-slate-500">
                  Tidak ada log aktivitas yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="logs.links && logs.links.length > 3" class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between sm:px-6">
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-slate-700">
                Menampilkan <span class="font-medium">{{ logs.from }}</span> - <span class="font-medium">{{ logs.to }}</span> dari <span class="font-medium">{{ logs.total }}</span> hasil
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <Link
                  v-for="(link, k) in logs.links"
                  :key="k"
                  :href="link.url || '#'"
                  v-html="link.label"
                  class="relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors"
                  :class="[
                    link.active 
                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                      : 'bg-white border-slate-300 text-slate-500 hover:bg-slate-50',
                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                />
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
  logs: Object,
  filters: Object,
});

const search = ref(props.filters.search || '');
const logLocations = reactive({});
const loadingLocation = reactive({});

const trackLocation = async (logId, ipAddress) => {
  // Cegah pencarian jika IP adalah localhost/private
  if (!ipAddress || ipAddress === '127.0.0.1' || ipAddress === '::1' || ipAddress.startsWith('192.168.')) {
    logLocations[logId] = 'IP Jaringan Lokal';
    return;
  }
  
  loadingLocation[logId] = true;
  try {
    // Memanggil API melalui proxy Backend Laravel agar terbebas dari masalah AdBlocker dan CORS Browser
    const response = await axios.get(route('superadmin.logs.location', { ip: ipAddress }));
    const data = response.data;
    
    if (data && data.city) {
      logLocations[logId] = `${data.city}, ${data.regionName}, ${data.country}`;
    } else {
      logLocations[logId] = 'Lokasi tidak ditemukan';
    }
  } catch (error) {
    logLocations[logId] = 'Gagal melacak lokasi (Diblokir/Timeout)';
  } finally {
    loadingLocation[logId] = false;
  }
};

let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(route('superadmin.logs'), { search: search.value }, {
      preserveState: true,
      replace: true,
    });
  }, 400);
};

const getActionClass = (action) => {
  if (action.includes('login')) {
    if (action.includes('failed')) return 'bg-red-50 text-red-700 border-red-200';
    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  }
  if (action.includes('logout')) return 'bg-slate-100 text-slate-700 border-slate-200';
  if (action.includes('delete')) return 'bg-rose-50 text-rose-700 border-rose-200';
  if (action.includes('create')) return 'bg-blue-50 text-blue-700 border-blue-200';
  if (action.includes('update')) return 'bg-amber-50 text-amber-700 border-amber-200';
  return 'bg-slate-100 text-slate-700 border-slate-200';
};
</script>

<style scoped>
.text-emerald-650 {
  color: rgb(4, 120, 87);
}
</style>
