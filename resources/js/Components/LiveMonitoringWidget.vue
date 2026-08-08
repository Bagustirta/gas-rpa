<template>
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
          <h3 class="font-bold text-slate-800 text-base">Live Monitoring: Cek Kuota</h3>
          <p class="text-xs text-slate-400 font-medium">Memeriksa sisa jatah gas pelanggan secara real-time</p>
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
            <span class="text-slate-800 font-bold">{{ liveData.pangkalan_index || 0 }} / {{ liveData.pangkalan_total || 0 }}</span>
          </div>
          <div class="h-3 w-full bg-slate-100 border border-slate-200 rounded-full overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-500"
              :style="{ width: liveData.pangkalan_total ? `${(liveData.pangkalan_index / liveData.pangkalan_total) * 100}%` : '0%' }"
            ></div>
          </div>
        </div>

        <!-- NIK Progress -->
        <div>
          <div class="flex justify-between text-xs font-semibold mb-2">
            <span class="text-slate-500">Progres Pengecekan NIK</span>
            <span class="text-slate-800 font-bold">
              {{ liveData.is_running && liveData.nik_current ? liveData.nik_current : 0 }} / {{ liveData.nik_total || 0 }} Antrean
            </span>
          </div>
          <div class="h-3 w-full bg-slate-100 border border-slate-200 rounded-full overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
              :style="{ width: liveData.nik_total ? `${(liveData.nik_current / liveData.nik_total) * 100}%` : '0%' }"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  initialData: Object,
});

const page = usePage();
const authUserId = page.props.auth.user?.id;
const liveData = ref(props.initialData);

// Sync with initialData changes from parent
watch(() => props.initialData, (newVal) => {
  liveData.value = newVal;
}, { deep: true });

onMounted(() => {
  if (window.Echo && authUserId) {
    window.Echo.private(`audit-progress.${authUserId}`)
      .listen('.AuditProgressUpdated', (data) => {
        const isRunning = data.is_running ?? false;
        if (!isRunning && data.finished_at) {
          liveData.value = null;
          return;
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
  color: #4f46e5;
}
.bg-indigo-550 {
  background-color: #4f46e5;
}
</style>
