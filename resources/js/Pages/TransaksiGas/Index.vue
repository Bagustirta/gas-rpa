<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Transaksi Gas LPG 3Kg</h2>
          <p class="text-xs text-slate-500">Catatan transaksi penjualan tabung gas melon bersubsidi</p>
        </div>
        <div class="grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:gap-2.5 w-full sm:w-auto mt-4 sm:mt-0">
          <!-- Reset Semua Bot (Visible only when activeSync atau activeAudit is true) -->
          <button
            v-if="page.props.activeSync || page.props.activeAudit"
            @click="resetSync"
            class="col-span-2 sm:col-span-1 w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-3 py-2.5 sm:py-2 rounded-xl bg-rose-600 hover:bg-rose-500 font-semibold text-xs text-white shadow-md shadow-rose-100 transition-all duration-200 active:scale-[0.98]"
            :title="page.props.activeAudit && !page.props.activeSync ? 'Hentikan pengecekan kuota' : 'Batalkan pelaporan'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ page.props.activeAudit && !page.props.activeSync ? 'Batalkan Cek' : (page.props.activeSync && !page.props.activeAudit ? 'Batalkan Lapor' : 'Batalkan Bot') }}</span>
          </button>

          <!-- Audit Quota Button -->
          <button
            @click="auditQuota"
            class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-3 py-2.5 sm:py-2 rounded-xl bg-sky-600 hover:bg-sky-500 font-semibold text-xs text-white shadow-md shadow-sky-100 transition-all duration-200 active:scale-[0.98]"
            title="Mengecek sisa jatah kuota tiap pelanggan"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Cek Kuota</span>
          </button>

          <!-- Mulai Sync Pertamina (RPA) -->
          <button
            @click="triggerRpa"
            class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-3 py-2.5 sm:py-2 rounded-xl bg-amber-600 hover:bg-amber-500 font-semibold text-xs text-white shadow-md shadow-amber-100 transition-all duration-200 active:scale-[0.98]"
            :class="{ 'opacity-50 cursor-not-allowed': page.props.activeSync }"
            :disabled="page.props.activeSync"
            title="Mengirim data transaksi ke MyPertamina"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span>Lapor Pertamina</span>
          </button>

          <!-- Riwayat RPA Button -->
          <button
            @click="showHistoryModal = true"
            class="col-span-2 sm:col-span-1 w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-3 py-2.5 sm:py-2 rounded-xl bg-slate-100 hover:bg-slate-200 font-semibold text-xs text-slate-700 transition-all duration-200 active:scale-[0.98]"
            title="Lihat riwayat pelaporan sebelumnya"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Riwayat Laporan</span>
          </button>

          <!-- Distribusi Otomatis -->
          <button
            @click="openDistModal"
            class="col-span-2 sm:col-span-1 w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-3 py-2.5 sm:py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 font-semibold text-xs text-white shadow-md shadow-emerald-100 transition-all duration-200 active:scale-[0.98]"
            title="Distribusi tabung otomatis"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <span>Distribusi Otomatis</span>
          </button>

          <!-- Bulk Action button -->
          <button
            v-if="selectedIds.length > 0"
            @click="bulkMarkAsReported"
            class="col-span-2 sm:col-span-1 w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-3 py-2.5 sm:py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs transition-all duration-200 active:scale-[0.98]"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span>Lapor Massal ({{ selectedIds.length }})</span>
          </button>
        </div>
      </div>

      <!-- RPA Progress Card (Mobile Optimized & Carousel) -->
      <div v-if="page.props.activeSync" class="bg-indigo-600 rounded-2xl p-4 md:p-5 shadow-lg shadow-indigo-200 text-white relative flex flex-col md:flex-row justify-between items-start md:items-center gap-4 group">
        <!-- SVG background pattern -->
        <div class="absolute inset-0 opacity-10 pointer-events-none rounded-2xl overflow-hidden">
          <svg width="100%" height="100%" fill="none"><pattern id="pattern-circles" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="2" fill="currentColor"></circle></pattern><rect x="0" y="0" width="100%" height="100%" fill="url(#pattern-circles)"></rect></svg>
        </div>
        
        <div class="relative z-10 w-full md:w-auto text-center md:text-left flex-shrink-0">
           <h3 class="font-bold text-lg text-white mb-0.5">
             Progres Lapor Pertamina
             <span v-if="rpaSlides.length > 1" class="ml-2 px-2 py-0.5 rounded-full bg-white/20 text-xs font-bold">{{ activeSlide === 0 ? 'Global' : rpaSlides[activeSlide].nama_pangkalan }}</span>
           </h3>
           <p v-if="rpaSummary.current_pangkalan && activeSlide === 0" class="text-indigo-200 text-xs font-medium">
              Sedang memproses: <span class="font-bold text-white">{{ rpaSummary.current_pangkalan }}</span>
           </p>
           <p v-else-if="activeSlide > 0" class="text-indigo-200 text-xs font-medium">
              Rincian pangkalan khusus
           </p>
           <p v-else class="text-indigo-200 text-xs font-medium">
              Mempersiapkan Pelaporan Otomatis...
           </p>
        </div>
        
        <div class="relative z-10 flex flex-wrap items-center justify-center md:justify-end gap-2 w-full overflow-hidden hide-scrollbar"
             @touchstart="onTouchStart" 
             @touchend="onTouchEnd"
             @mouseenter="pauseSlideShow"
             @mouseleave="resumeSlideShow">
           
           <button v-if="rpaSlides.length > 1" @click="handleSwipeRight" class="absolute left-0 top-1/2 -translate-y-1/2 -ml-2 p-1.5 rounded-full bg-indigo-800/50 hover:bg-indigo-800 text-white shadow-md z-20 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
           </button>

           <div class="flex flex-wrap items-center justify-center md:justify-end gap-2 w-full transition-all duration-300">
             <!-- Blok Sukses -->
             <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl px-4 py-3 flex flex-col items-center justify-center min-w-[85px]">
                <div class="flex items-center gap-1.5 mb-0.5">
                   <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                   <span class="text-xs uppercase tracking-wider font-bold text-indigo-100">Sukses</span>
                </div>
                <span class="text-3xl font-black text-emerald-400 drop-shadow-sm">{{ rpaSlides[activeSlide].sukses }}</span>
             </div>
             
             <!-- Blok Limit -->
             <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl px-4 py-3 flex flex-col items-center justify-center min-w-[85px]">
                <div class="flex items-center gap-1.5 mb-0.5">
                   <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                   <span class="text-xs uppercase tracking-wider font-bold text-indigo-100">Limit</span>
                </div>
                <span class="text-3xl font-black text-blue-400 drop-shadow-sm">{{ rpaSlides[activeSlide].kuota_habis || 0 }}</span>
             </div>
             
             <!-- Blok Antre -->
             <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl px-4 py-3 flex flex-col items-center justify-center min-w-[85px]">
                <div class="flex items-center gap-1.5 mb-0.5">
                   <svg class="w-4 h-4 text-amber-300 animate-spin-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 11H19.21"></path></svg>
                   <span class="text-xs uppercase tracking-wider font-bold text-indigo-100">Antre</span>
                </div>
                <span class="text-3xl font-black text-amber-300 drop-shadow-sm" :class="{ 'animate-pulse': rpaSlides[activeSlide].belum_lapor > 0 }">{{ rpaSlides[activeSlide].belum_lapor }}</span>
             </div>
             
             <!-- Blok Gagal -->
             <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl px-4 py-3 flex flex-col items-center justify-center min-w-[85px]">
                <div class="flex items-center gap-1.5 mb-0.5">
                   <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                   <span class="text-xs uppercase tracking-wider font-bold text-indigo-100">Gagal</span>
                </div>
                <span class="text-3xl font-black text-rose-400 drop-shadow-sm">{{ rpaSlides[activeSlide].gagal }}</span>
             </div>
             
             <!-- Blok Total -->
             <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl px-4 py-3 flex flex-col items-center justify-center min-w-[85px]">
                <div class="flex items-center gap-1.5 mb-0.5">
                   <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                   <span class="text-xs uppercase tracking-wider font-bold text-indigo-100">Total</span>
                </div>
                <span class="text-3xl font-black text-white drop-shadow-sm">{{ rpaSlides[activeSlide].total }}</span>
             </div>
           </div>

           <button v-if="rpaSlides.length > 1" @click="handleSwipeLeft" class="absolute right-0 top-1/2 -translate-y-1/2 -mr-2 p-1.5 rounded-full bg-indigo-800/50 hover:bg-indigo-800 text-white shadow-md z-20 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
           </button>
           
           <!-- Carousel Dots Indicator -->
           <div v-if="rpaSlides.length > 1" class="absolute -bottom-2 left-1/2 -translate-x-1/2 flex space-x-1.5">
              <button v-for="(_, i) in rpaSlides" :key="i" @click="goToSlide(i)" :class="activeSlide === i ? 'w-4 bg-white' : 'w-1.5 bg-white/40'" class="h-1.5 rounded-full transition-all duration-300"></button>
           </div>
        </div>
      </div>

      <!-- Live Monitoring RPA Sync -->
      <LiveMonitoringWidget v-if="page.props.auditProgress" :initialData="page.props.auditProgress" class="mt-4" />

      <!-- Filters & Search Bar -->
      <div class="bg-white border border-slate-300 rounded-2xl p-4 md:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center shadow-sm">
        <!-- Search NIK -->
        <div class="relative w-full">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Cari NIK..."
            class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-sm transition-all duration-200"
          />
        </div>

        <!-- Filter Status Lapor -->
        <div class="w-full">
          <select
            v-model="filters.status_lapor"
            @change="applyFilters"
            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 font-semibold focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-sm transition-all duration-200"
          >
            <option value="">Semua Status Lapor</option>
            <option value="belum_lapor">Belum Lapor</option>
            <option value="proses">Proses Antrean</option>
            <option value="sukses">Sukses Dilaporkan</option>
            <option value="kuota_habis">Limit Pertamina</option>
            <option value="belum_17_tahun">Belum 17 Tahun</option>
            <option value="gagal">Gagal Dilaporkan</option>
            <option value="meninggal">Meninggal Dunia</option>
          </select>
        </div>

        <!-- Filter Pangkalan -->
        <div class="w-full">
          <select
            v-model="filters.akun_pangkalan_id"
            @change="applyFilters"
            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 font-semibold focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-sm transition-all duration-200"
          >
            <option value="">Semua Pangkalan</option>
            <option v-for="p in pangkalans" :key="p.id" :value="p.id">
              {{ p.nama_pangkalan }}
            </option>
          </select>
        </div>

        <!-- Filter Rentang Tanggal (Visual Calendar Grid Control) -->
        <div class="relative w-full" ref="datePickerContainerRef">
          <button
            type="button"
            @click="toggleDatePicker"
            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 font-semibold focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-sm transition-all duration-200 flex items-center justify-between"
          >
            <div class="flex items-center space-x-2 truncate">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="truncate font-semibold">{{ dateRangeLabel }}</span>
            </div>
            <button
              v-if="filters.tanggal_mulai || filters.tanggal_selesai"
              @click.stop="clearDateRange"
              class="text-slate-400 hover:text-slate-600 ml-1 p-0.5"
              title="Hapus filter tanggal"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </button>

          <!-- Calendar Popover (Sesuai Gambar) -->
          <div 
            v-if="showDatePicker" 
            class="absolute right-0 mt-2 w-72 sm:w-80 bg-white border border-slate-200 rounded-2xl p-4 shadow-2xl z-50 space-y-3"
          >
            <!-- Month Header Navigation -->
            <div class="flex items-center justify-between px-1">
              <button 
                type="button" 
                @click="prevMonth" 
                class="p-1 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors"
                title="Bulan sebelumnya"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <span class="text-xs font-bold text-slate-800">{{ calendarTitle }}</span>

              <button 
                type="button" 
                @click="nextMonth" 
                class="p-1 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors"
                title="Bulan selanjutnya"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            <!-- Days of Week Header -->
            <div class="grid grid-cols-7 text-center text-[11px] font-semibold text-slate-400 py-1">
              <span>Sn</span>
              <span>Sl</span>
              <span>Rb</span>
              <span>Km</span>
              <span>Jm</span>
              <span>Sb</span>
              <span>Mg</span>
            </div>

            <!-- Calendar Days Grid -->
            <div class="grid grid-cols-7 gap-y-1 text-center text-xs">
              <button
                v-for="(item, idx) in calendarDays"
                :key="idx"
                type="button"
                @click="selectCalendarDate(item.dateStr)"
                :disabled="!item.isCurrentMonth"
                :class="[
                  'h-8 w-full flex items-center justify-center text-xs font-semibold transition-all duration-150 relative',
                  getDateClass(item)
                ]"
              >
                {{ item.day }}
              </button>
            </div>

            <!-- Preset & Actions Footer -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
              <div class="flex items-center space-x-1.5">
                <button 
                  type="button" 
                  @click="setPresetRange('today')" 
                  class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-700 transition-colors"
                >
                  Hari Ini
                </button>
                <button 
                  type="button" 
                  @click="setPresetRange('thisMonth')" 
                  class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-700 transition-colors"
                >
                  Bulan Ini
                </button>
              </div>

              <div class="flex items-center space-x-2">
                <button 
                  type="button" 
                  @click="clearDateRange" 
                  class="px-2.5 py-1 text-xs font-semibold rounded text-slate-500 hover:bg-slate-100"
                >
                  Reset
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-xs text-slate-400 uppercase font-bold tracking-wider">
                <th class="py-3 px-4 w-12 text-center">
                  <input
                    type="checkbox"
                    :checked="isAllSelected"
                    @change="toggleSelectAll"
                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-550"
                  />
                </th>
                <th class="py-3 px-4">Pelanggan NIK</th>
                <th class="py-3 px-4">Pangkalan</th>
                <th class="py-3 px-4 text-center">Tabung</th>
                <th class="py-3 px-4">Tanggal Transaksi</th>
                <th class="py-3 px-4 text-center">Status Lapor</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr
                v-for="t in localTransaksis"
                :key="t.id"
                :class="[
                  'hover:bg-slate-50/70 transition-all duration-150',
                  selectedIds.includes(t.id) ? 'bg-indigo-50/30' : ''
                ]"
              >
                <!-- Checkbox -->
                <td class="py-3.5 px-4 text-center">
                  <input
                    type="checkbox"
                    :value="t.id"
                    v-model="selectedIds"
                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-550"
                  />
                </td>
                <!-- NIK -->
                <td class="py-3.5 px-4">
                  <div class="flex flex-col">
                    <div class="flex items-center space-x-2 font-mono text-slate-800 tracking-wider">
                      <span>{{ revealedNikMap[t.id] ? t.pelanggan_nik : maskNik(t.pelanggan_nik) }}</span>
                      <button @click="toggleNikShow(t.id)" class="text-slate-400 hover:text-indigo-600 transition-colors p-0.5" :title="revealedNikMap[t.id] ? 'Sembunyikan NIK' : 'Tampilkan NIK'">
                        <svg v-if="revealedNikMap[t.id]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.88 9.88a3 3 0 104.24 4.24m-1.41-5.65L18.36 18.36M1 1l22 22" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>
                    </div>
                    <span v-if="!t.pelanggan_status" class="text-[9px] text-red-500 font-semibold uppercase tracking-wider mt-0.5">⚠️ NIK Nonaktif</span>
                  </div>
                </td>
                <!-- Pangkalan -->
                <td class="py-3.5 px-4 text-slate-700 font-medium truncate max-w-[150px]">
                  {{ t.nama_pangkalan }}
                </td>
                <!-- Jumlah Tabung -->
                <td class="py-3.5 px-4 text-center font-bold text-slate-800">
                  {{ t.jumlah_tabung }}
                </td>
                <!-- Tanggal -->
                <td class="py-3.5 px-4 text-slate-500 font-mono text-xs">
                  {{ t.tanggal_transaksi }}
                </td>
                <!-- Status Lapor -->
                <td class="py-3.5 px-4 text-center">
                  <div class="flex flex-col items-center">
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                      t.status_lapor === 'sukses' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                      t.status_lapor === 'kuota_habis' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                      t.status_lapor === 'belum_17_tahun' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                      t.status_lapor === 'meninggal' ? 'bg-zinc-100 text-zinc-700 border-zinc-300' :
                      t.status_lapor === 'gagal' ? 'bg-red-50 text-red-700 border-red-200' :
                      t.status_lapor === 'proses' ? 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse' :
                      'bg-slate-100 text-slate-500 border-slate-200'
                    ]">
                      {{ 
                        t.status_lapor === 'belum_17_tahun' ? 'Belum 17 Thn' : 
                        t.status_lapor === 'meninggal' ? 'Meninggal' :
                        t.status_lapor.replace('_', ' ') 
                      }}
                    </span>
                    <span v-if="t.catatan_lapor" class="text-[9px] text-slate-400 mt-1 max-w-[120px] truncate" :title="t.catatan_lapor">
                      {{ t.catatan_lapor }}
                    </span>
                  </div>
                </td>
                <!-- Actions -->
                <td class="py-3.5 px-4 text-right">
                  <div class="inline-flex space-x-1">
                    <button
                      v-if="t.status_lapor !== 'sukses'"
                      @click="markAsReported(t)"
                      title="Tandai Sukses Lapor"
                      class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 hover:bg-emerald-100/50 text-emerald-600 transition-all duration-200 shadow-sm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
                      </svg>
                    </button>

                    <button
                      @click="confirmDelete(t)"
                      class="p-2 rounded-lg bg-white border border-slate-200 hover:border-red-250 hover:bg-red-50 hover:text-red-600 text-slate-500 transition-all duration-200 shadow-sm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="localTransaksis.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-xs text-slate-400 uppercase tracking-widest font-bold">
                  Tidak ada data transaksi ditemukan
                </td>
              </tr>
            </tbody>
          </table>
        </div>

    <!-- Infinite Scroll Trigger -->
    <div ref="loadMoreTrigger" class="flex items-center justify-center py-6 mt-4">
      <div v-if="transaksis.next_page_url" class="flex items-center space-x-2 text-slate-500">
        <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-medium">Memuat data selanjutnya...</span>
      </div>
      <div v-else-if="localTransaksis.length > 0" class="text-xs font-medium text-slate-400 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
        Semua {{ transaksis.total }} transaksi telah dimuat
      </div>
    </div>
  </div>



      <!-- Delete Confirmation -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-sm w-full p-6 shadow-2xl">
            <h3 class="font-bold text-slate-900 text-base mb-3 flex items-center space-x-2">
              <span class="text-red-500">⚠️</span>
              <span>Hapus Transaksi?</span>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-6 font-medium">
              Apakah Anda yakin ingin menghapus catatan transaksi untuk pelanggan <strong class="text-slate-850 font-mono">{{ activeTransaksi?.pelanggan_nik }}</strong> berjumlah <strong class="text-slate-800">{{ activeTransaksi?.jumlah_tabung }} tabung</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="flex justify-end space-x-3">
              <button
                @click="showDeleteModal = false"
                class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 hover:text-slate-800 text-xs font-bold text-slate-600 transition-all duration-200"
              >
                Batal
              </button>
              <button
                @click="destroy"
                class="px-4 py-2 rounded-xl bg-red-650 hover:bg-red-600 font-semibold text-xs text-white shadow-md active:scale-[0.98] transition-all duration-150"
              >
                Hapus Permanen
              </button>
            </div>
          </div>
        </div>
      </transition>
      <!-- Distribusi Otomatis Modal -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showDistModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <h3 class="font-bold text-slate-900 text-base mb-2">
              Distribusi Tabung Otomatis
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-6 font-medium">
              Fitur ini akan otomatis membuat transaksi penjualan gas LPG 3 Kg (maksimal 1 tabung per minggu per pelanggan) secara merata di seluruh minggu pada bulan berjalan.
            </p>

            <form @submit.prevent="submitDistribusi" class="space-y-4">
              <!-- Akun Pangkalan Select -->
              <div>
                <label for="dist-pangkalan" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">Akun Pangkalan Target</label>
                <select
                  id="dist-pangkalan"
                  v-model="distForm.akun_pangkalan_id"
                  required
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-medium focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-sm transition-all duration-200"
                  :class="{ 'border-red-500 bg-red-50/10': distForm.errors.akun_pangkalan_id }"
                >
                  <option value="">Pilih Akun Pangkalan...</option>
                  <option v-for="p in pangkalans" :key="p.id" :value="p.id">
                    {{ p.nama_pangkalan }}
                  </option>
                </select>
                <p v-if="distForm.errors.akun_pangkalan_id" class="text-xs text-red-500 mt-1 font-medium">{{ distForm.errors.akun_pangkalan_id }}</p>
              </div>

              <!-- Total Tabung -->
              <div>
                <label for="dist-total" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">Total Tabung Diterima</label>
                <input
                  id="dist-total"
                  v-model.number="distForm.total_tabung"
                  type="number"
                  min="1"
                  required
                  placeholder="Contoh: 50"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-sm transition-all duration-200"
                  :class="{ 'border-red-500 bg-red-50/10': distForm.errors.total_tabung }"
                />
                <p v-if="distForm.errors.total_tabung" class="text-xs text-red-500 mt-1 font-medium">{{ distForm.errors.total_tabung }}</p>
                <p class="text-[11px] text-slate-500 mt-1 font-normal leading-relaxed">Sistem akan mengalokasikan tabung ini ke pelanggan secara otomatis berdasarkan sisa kuota mereka.</p>
              </div>

              <!-- Tanggal Transaksi -->
              <div>
                <label for="dist-tanggal" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">Tanggal Transaksi</label>
                <input
                  id="dist-tanggal"
                  v-model="distForm.tanggal_transaksi"
                  type="date"
                  required
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-sm transition-all duration-200"
                  :class="{ 'border-red-500 bg-red-50/10': distForm.errors.tanggal_transaksi }"
                />
                <p v-if="distForm.errors.tanggal_transaksi" class="text-xs text-red-500 mt-1 font-medium">{{ distForm.errors.tanggal_transaksi }}</p>
                <p class="text-[11px] text-slate-500 mt-1 font-normal leading-relaxed">Transaksi akan diutamakan pada minggu dari tanggal ini, lalu didistribusikan ke minggu lain pada bulan yang sama jika kuota minggu ini habis.</p>
              </div>

              <!-- Footer -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-slate-200 mt-6">
                <button
                  type="button"
                  @click="showDistModal = false"
                  class="px-4 py-2 rounded-xl border border-slate-300 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-all duration-200"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="distForm.processing"
                  class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 font-semibold text-xs text-white shadow-sm active:scale-[0.98] transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ distForm.processing ? 'Memproses...' : 'Mulai Distribusi' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>

      <!-- Riwayat RPA Modal -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-4xl w-full p-4 md:p-6 shadow-2xl max-h-[85vh] flex flex-col">
            <div class="flex justify-between items-center mb-4">
              <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Riwayat Sinkronisasi RPA
              </h3>
              <button @click="showHistoryModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            
            <div class="overflow-y-auto overflow-x-auto pr-2 flex-1 border border-slate-100 rounded-xl">
              <table class="w-full text-left border-collapse min-w-[600px]">
                <thead class="sticky top-0 bg-slate-50 z-10">
                  <tr class="border-b border-slate-200 text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                    <th class="py-2.5 px-4">Waktu</th>
                    <th class="py-2.5 px-4 text-center">Status</th>
                    <th class="py-2.5 px-4 text-center">Data</th>
                    <th class="py-2.5 px-4">Catatan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                  <tr v-for="history in syncHistory" :key="history.id" class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3 px-4 font-mono text-slate-500">
                      {{ history.created_at }}
                    </td>
                    <td class="py-3 px-4 text-center">
                      <span :class="[
                        'px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider',
                        history.status === 'completed' ? 'bg-emerald-100 text-emerald-700' :
                        history.status === 'failed' ? 'bg-rose-100 text-rose-700' :
                        'bg-amber-100 text-amber-700 animate-pulse'
                      ]">
                        {{ history.status === 'completed' ? 'Selesai' : (history.status === 'failed' ? 'Gagal' : (history.status === 'processing' ? 'Proses' : 'Antre')) }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                      <div class="flex items-center justify-center space-x-2 text-[10px] font-bold">
                        <span class="text-indigo-500" title="Total">T:{{ history.total_records }}</span>
                        <span class="text-emerald-500" title="Sukses">S:{{ history.success_records }}</span>
                        <span class="text-blue-500" title="Limit (Kuota Habis)">L:{{ history.limit_records || 0 }}</span>
                        <span class="text-rose-500" title="Gagal">G:{{ history.failed_records }}</span>
                      </div>
                    </td>
                    <td class="py-3 px-4 text-slate-600 whitespace-normal break-words max-w-xs md:max-w-md">
                      {{ history.message || '-' }}
                    </td>
                  </tr>
                  <tr v-if="!syncHistory || syncHistory.length === 0">
                    <td colspan="4" class="py-8 text-center text-slate-400 text-xs font-semibold">Belum ada riwayat sinkronisasi.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="mt-4 flex justify-end">
              <button @click="showHistoryModal = false" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-md transition-all active:scale-95">Tutup</button>
            </div>
          </div>
        </div>
      </transition>

      <!-- RPA Confirmation Modal -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showRpaConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-sm w-full p-6 shadow-2xl">
            <div class="flex items-start space-x-4 mb-5">
              <div :class="[
                'flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                rpaConfirmType === 'rpa' ? 'bg-amber-100 text-amber-600' :
                rpaConfirmType === 'audit' ? 'bg-sky-100 text-sky-600' :
                'bg-rose-100 text-rose-600'
              ]">
                <!-- RPA Icon -->
                <svg v-if="rpaConfirmType === 'rpa'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <!-- Audit Icon -->
                <svg v-else-if="rpaConfirmType === 'audit'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <!-- Reset Icon -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-900 text-sm mb-1">{{ rpaConfirmData.title }}</h3>
                <p class="text-xs text-slate-500 leading-relaxed">{{ rpaConfirmData.message }}</p>
              </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
              <button
                @click="showRpaConfirmModal = false"
                class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-600 transition-all duration-200"
              >
                Batal
              </button>
              <button
                @click="executeRpaAction"
                :class="[
                  'px-4 py-2 rounded-xl font-semibold text-xs text-white shadow-md active:scale-[0.98] transition-all duration-150',
                  rpaConfirmType === 'rpa' ? 'bg-amber-600 hover:bg-amber-500 shadow-amber-100' :
                  rpaConfirmType === 'audit' ? 'bg-sky-600 hover:bg-sky-500 shadow-sky-100' :
                  'bg-rose-600 hover:bg-rose-500 shadow-rose-100'
                ]"
              >
                {{ rpaConfirmData.buttonLabel }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import LiveMonitoringWidget from '../../Components/LiveMonitoringWidget.vue';

const page = usePage();
const authUserId = computed(() => page.props.auth.user.id);
const transaksis = computed(() => page.props.transaksis);
const rpaSummary = computed(() => page.props.rpaSummary || { total: 0, sukses: 0, kuota_habis: 0, belum_lapor: 0, gagal: 0, breakdowns: [] });
const pangkalans = computed(() => page.props.pangkalans);
const syncHistory = computed(() => page.props.syncHistory || []);

const revealedNikMap = ref({});
const toggleNikShow = (id) => {
  revealedNikMap.value[id] = !revealedNikMap.value[id];
};

const maskNik = (nik) => {
  if (!nik || nik.length < 12) return nik || '-';
  return nik.substring(0, 6) + '******' + nik.substring(12);
};

// Carousel state
const activeSlide = ref(0);
const rpaSlides = computed(() => {
  const sum = rpaSummary.value;
  const slides = [{
    nama_pangkalan: 'Total Global',
    total: sum.total || 0,
    sukses: sum.sukses || 0,
    kuota_habis: sum.kuota_habis || 0,
    belum_lapor: sum.belum_lapor || 0,
    gagal: sum.gagal || 0
  }];
  if (sum.breakdowns && sum.breakdowns.length > 0) {
    slides.push(...sum.breakdowns);
  }
  return slides;
});

// Fitur Geser (Swipe) untuk Mobile & Auto Slide
const touchStartX = ref(0);
const touchEndX = ref(0);
let slideInterval = null;

const onTouchStart = (e) => {
  touchStartX.value = e.changedTouches[0].screenX;
  pauseSlideShow();
};

const onTouchEnd = (e) => {
  touchEndX.value = e.changedTouches[0].screenX;
  handleSwipe();
  resumeSlideShow();
};

const handleSwipe = () => {
  if (rpaSlides.value.length <= 1) return;
  if (touchEndX.value < touchStartX.value - 40) {
    handleSwipeLeft();
  }
  if (touchEndX.value > touchStartX.value + 40) {
    handleSwipeRight();
  }
};

const handleSwipeLeft = () => {
  activeSlide.value = activeSlide.value < rpaSlides.value.length - 1 ? activeSlide.value + 1 : 0;
  pauseSlideShow();
  resumeSlideShow();
};

const handleSwipeRight = () => {
  activeSlide.value = activeSlide.value > 0 ? activeSlide.value - 1 : rpaSlides.value.length - 1;
  pauseSlideShow();
  resumeSlideShow();
};

const goToSlide = (index) => {
  activeSlide.value = index;
  pauseSlideShow();
  resumeSlideShow();
};

const pauseSlideShow = () => {
  if (slideInterval) clearInterval(slideInterval);
};

const resumeSlideShow = () => {
  pauseSlideShow();
  if (rpaSlides.value.length > 1) {
    slideInterval = setInterval(() => {
      activeSlide.value = activeSlide.value < rpaSlides.value.length - 1 ? activeSlide.value + 1 : 0;
    }, 4000); // Ganti tiap 4 detik
  }
};

// Start slideshow if multiple slides exist
watch(rpaSlides, (newVal) => {
  if (newVal.length > 1) {
    resumeSlideShow();
  } else {
    pauseSlideShow();
  }
}, { immediate: true });

const filters = reactive({
  search: page.props.filters?.search || '',
  status_lapor: page.props.filters?.status_lapor || '',
  akun_pangkalan_id: page.props.filters?.akun_pangkalan_id || '',
  tanggal_mulai: page.props.filters?.tanggal_mulai || '',
  tanggal_selesai: page.props.filters?.tanggal_selesai || '',
});

// Current filters for building page URLs
const currentFilters = computed(() => ({
  search: filters.search,
  status_lapor: filters.status_lapor,
  akun_pangkalan_id: filters.akun_pangkalan_id,
  tanggal_mulai: filters.tanggal_mulai,
  tanggal_selesai: filters.tanggal_selesai,
}));

// Infinite Scroll Logic
const localTransaksis = ref([...(transaksis.value?.data || [])]);
const loadMoreTrigger = ref(null);
const isLoadingMore = ref(false);
let observer = null;

watch(() => transaksis.value, (newVal) => {
  if (!newVal) return;
  if (newVal.current_page === 1) {
    localTransaksis.value = [...newVal.data];
  } else {
    localTransaksis.value = [...localTransaksis.value, ...newVal.data];
  }
  isLoadingMore.value = false;
}, { deep: true });

const loadMore = () => {
  if (transaksis.value.next_page_url && !isLoadingMore.value) {
    isLoadingMore.value = true;
    router.get(transaksis.value.next_page_url, {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['transaksis'],
    });
  }
};

const showDeleteModal = ref(false);
const showDistModal = ref(false);
const showHistoryModal = ref(false);
const activeTransaksi = ref(null);

// RPA Confirmation Modal State
const showRpaConfirmModal = ref(false);
const rpaConfirmType = ref('rpa'); // 'rpa' | 'audit' | 'reset'
const rpaConfirmData = ref({ title: '', message: '', buttonLabel: '' });

// Distribusi Otomatis Form
const distForm = useForm({
  akun_pangkalan_id: '',
  total_tabung: '',
  tanggal_transaksi: '',
});

// Bulk action states
const selectedIds = ref([]);

const isAllSelected = computed(() => {
  return localTransaksis.value.length > 0 && selectedIds.value.length === localTransaksis.value.length;
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = localTransaksis.value.map(t => t.id);
  }
};

// Visual Calendar Range Picker Logic
const showDatePicker = ref(false);
const datePickerContainerRef = ref(null);
const tempStart = ref(filters.tanggal_mulai);
const tempEnd = ref(filters.tanggal_selesai);

const initialDateObj = filters.tanggal_mulai ? new Date(filters.tanggal_mulai) : new Date();
const calendarMonth = ref(isNaN(initialDateObj) ? new Date().getMonth() : initialDateObj.getMonth());
const calendarYear = ref(isNaN(initialDateObj) ? new Date().getFullYear() : initialDateObj.getFullYear());

const monthNamesIndo = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const calendarTitle = computed(() => {
  return `${monthNamesIndo[calendarMonth.value]} ${calendarYear.value}`;
});

const prevMonth = () => {
  if (calendarMonth.value === 0) {
    calendarMonth.value = 11;
    calendarYear.value--;
  } else {
    calendarMonth.value--;
  }
};

const nextMonth = () => {
  if (calendarMonth.value === 11) {
    calendarMonth.value = 0;
    calendarYear.value++;
  } else {
    calendarMonth.value++;
  }
};

const padNum = (n) => String(n).padStart(2, '0');

const calendarDays = computed(() => {
  const year = calendarYear.value;
  const month = calendarMonth.value;

  const firstDayObj = new Date(year, month, 1);
  let dayOfWeek = firstDayObj.getDay(); 
  // Senin = 0, Selasa = 1, ..., Minggu = 6
  let startingIndex = dayOfWeek === 0 ? 6 : dayOfWeek - 1;

  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();

  const days = [];

  // Prev month padding
  for (let i = startingIndex - 1; i >= 0; i--) {
    const dayNum = daysInPrevMonth - i;
    const pMonth = month === 0 ? 11 : month - 1;
    const pYear = month === 0 ? year - 1 : year;
    days.push({
      day: dayNum,
      dateStr: `${pYear}-${padNum(pMonth + 1)}-${padNum(dayNum)}`,
      isCurrentMonth: false,
    });
  }

  // Current month
  for (let d = 1; d <= daysInMonth; d++) {
    days.push({
      day: d,
      dateStr: `${year}-${padNum(month + 1)}-${padNum(d)}`,
      isCurrentMonth: true,
    });
  }

  // Next month padding to 35 or 42
  const totalSoFar = days.length;
  const targetTotal = totalSoFar > 35 ? 42 : 35;
  for (let i = 1; i <= targetTotal - totalSoFar; i++) {
    const nMonth = month === 11 ? 0 : month + 1;
    const nYear = month === 11 ? year + 1 : year;
    days.push({
      day: i,
      dateStr: `${nYear}-${padNum(nMonth + 1)}-${padNum(i)}`,
      isCurrentMonth: false,
    });
  }

  return days;
});

const selectCalendarDate = (dateStr) => {
  if (!dateStr) return;

  if (!tempStart.value || (tempStart.value && tempEnd.value)) {
    tempStart.value = dateStr;
    tempEnd.value = null;
  } else if (tempStart.value && !tempEnd.value) {
    if (dateStr < tempStart.value) {
      tempEnd.value = tempStart.value;
      tempStart.value = dateStr;
    } else {
      tempEnd.value = dateStr;
    }
    // Langsung terapkan filter ketika rentang selesai dipilih!
    filters.tanggal_mulai = tempStart.value;
    filters.tanggal_selesai = tempEnd.value;
    showDatePicker.value = false;
    applyFilters();
  }
};

const getDateClass = (item) => {
  if (!item || !item.isCurrentMonth) return 'text-slate-300 opacity-40 cursor-not-allowed';

  const isStart = tempStart.value === item.dateStr;
  const isEnd = tempEnd.value === item.dateStr;
  const inRange = tempStart.value && tempEnd.value && item.dateStr > tempStart.value && item.dateStr < tempEnd.value;
  const isSunday = item.dateStr ? new Date(item.dateStr).getDay() === 0 : false;

  if (isStart && isEnd) {
    return 'bg-blue-600 !text-white font-bold rounded-xl shadow-sm z-10';
  }
  if (isStart && tempEnd.value) {
    return 'bg-blue-600 !text-white font-bold rounded-l-xl rounded-r-none shadow-sm z-10';
  }
  if (isEnd && tempStart.value) {
    return 'bg-blue-600 !text-white font-bold rounded-r-xl rounded-l-none shadow-sm z-10';
  }
  if (isStart || isEnd) {
    return 'bg-blue-600 !text-white font-bold rounded-xl shadow-sm z-10';
  }
  if (inRange) {
    return 'bg-blue-50 text-blue-950 font-semibold rounded-none';
  }
  if (isSunday) {
    return 'text-red-500 font-semibold hover:bg-red-50 cursor-pointer rounded-xl';
  }
  return 'hover:bg-blue-50 hover:text-blue-600 text-slate-800 cursor-pointer rounded-xl';
};

const formatDateDisplay = (dateStr) => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length === 3) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const year = parts[0];
    const month = months[parseInt(parts[1], 10) - 1] || parts[1];
    const day = parts[2];
    return `${day} ${month} ${year}`;
  }
  return dateStr;
};

const dateRangeLabel = computed(() => {
  if (filters.tanggal_mulai && filters.tanggal_selesai) {
    return `${formatDateDisplay(filters.tanggal_mulai)} – ${formatDateDisplay(filters.tanggal_selesai)}`;
  }
  if (filters.tanggal_mulai) {
    return `Dari ${formatDateDisplay(filters.tanggal_mulai)}`;
  }
  if (filters.tanggal_selesai) {
    return `s/d ${formatDateDisplay(filters.tanggal_selesai)}`;
  }
  return 'Atur Rentang Waktu';
});

const toggleDatePicker = () => {
  tempStart.value = filters.tanggal_mulai;
  tempEnd.value = filters.tanggal_selesai;
  showDatePicker.value = !showDatePicker.value;
};

const setPresetRange = (type) => {
  const now = new Date();
  const formatYMD = (d) => `${d.getFullYear()}-${padNum(d.getMonth() + 1)}-${padNum(d.getDate())}`;

  let start = '';
  let end = '';

  if (type === 'today') {
    start = formatYMD(now);
    end = start;
  } else if (type === 'thisMonth') {
    const s = new Date(now.getFullYear(), now.getMonth(), 1);
    const e = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    start = formatYMD(s);
    end = formatYMD(e);
  }

  tempStart.value = start;
  tempEnd.value = end;
  filters.tanggal_mulai = start;
  filters.tanggal_selesai = end;
  showDatePicker.value = false;
  applyFilters();
};

const clearDateRange = () => {
  filters.tanggal_mulai = '';
  filters.tanggal_selesai = '';
  tempStart.value = null;
  tempEnd.value = null;
  showDatePicker.value = false;
  applyFilters();
};

const handleOutsideClick = (e) => {
  if (datePickerContainerRef.value && !datePickerContainerRef.value.contains(e.target)) {
    showDatePicker.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick);
});

let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 400);
};

const applyFilters = () => {
  router.get(route('transaksi-gas.index'), {
    search: filters.search,
    status_lapor: filters.status_lapor,
    akun_pangkalan_id: filters.akun_pangkalan_id,
    tanggal_mulai: filters.tanggal_mulai,
    tanggal_selesai: filters.tanggal_selesai,
  }, {
    preserveState: true,
    replace: true,
  });
};

const markAsReported = (t) => {
  router.post(route('transaksi-gas.tandai-lapor', t.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
    }
  });
};

const bulkMarkAsReported = () => {
  if (selectedIds.value.length === 0) return;
  router.post(route('transaksi-gas.tandai-lapor-massal'), {
    ids: selectedIds.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
    }
  });
};

const confirmDelete = (t) => {
  activeTransaksi.value = t;
  showDeleteModal.value = true;
};

const destroy = () => {
  router.delete(route('transaksi-gas.destroy', activeTransaksi.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false;
      activeTransaksi.value = null;
      selectedIds.value = [];
    },
  });
};

const triggerRpa = () => {
  rpaConfirmType.value = 'rpa';
  rpaConfirmData.value = {
    title: 'Mulai Sinkronisasi Otomatis?',
    message: 'Ini akan mengaktifkan Bot RPA di PC Anda untuk login secara berantai ke semua akun pangkalan terdaftar, lalu menginput transaksi yang belum terlaporkan. Pastikan komputer Anda terhubung internet.',
    buttonLabel: 'Ya, Jalankan RPA',
  };
  showRpaConfirmModal.value = true;
};

const auditQuota = () => {
  rpaConfirmType.value = 'audit';
  rpaConfirmData.value = {
    title: 'Mulai Audit Kuota Pelanggan?',
    message: 'Ini akan menjalankan bot secara gaib di latar belakang untuk melakukan audit/pencocokan kuota pelanggan bulan berjalan ke portal Pertamina. Silakan pantau widget live progress di dashboard.',
    buttonLabel: 'Ya, Jalankan Audit',
  };
  showRpaConfirmModal.value = true;
};

const resetSync = () => {
  rpaConfirmType.value = 'reset';
  rpaConfirmData.value = {
    title: 'Reset Antrean Sinkronisasi?',
    message: 'Gunakan ini jika status sinkronisasi macet. Ini akan mengubah status antrean terakhir menjadi GAGAL dan membolehkan Anda menjalankan sinkronisasi baru.',
    buttonLabel: 'Ya, Reset Sekarang',
  };
  showRpaConfirmModal.value = true;
};

const executeRpaAction = () => {
  showRpaConfirmModal.value = false;
  if (rpaConfirmType.value === 'rpa') {
    router.post(route('transaksi-gas.trigger-rpa'), {}, { preserveScroll: true });
  } else if (rpaConfirmType.value === 'audit') {
    router.post(route('transaksi-gas.audit-quota'), {}, { preserveScroll: true });
  } else if (rpaConfirmType.value === 'reset') {
    router.post(route('transaksi-gas.reset-sync'), {}, { preserveScroll: true });
  }
};

const openDistModal = () => {
  distForm.reset();
  distForm.clearErrors();
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  distForm.tanggal_transaksi = `${year}-${month}-${day}`;
  distForm.akun_pangkalan_id = '';
  distForm.total_tabung = '';
  showDistModal.value = true;
};

const submitDistribusi = () => {
  distForm.post(route('transaksi-gas.distribusi-otomatis'), {
    onSuccess: () => {
      showDistModal.value = false;
    }
  });
};

onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      loadMore();
    }
  }, { rootMargin: '300px' });
  
  if (loadMoreTrigger.value) {
    observer.observe(loadMoreTrigger.value);
  }

  // 1. Minta Izin Notifikasi Browser
  if ("Notification" in window) {
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
      Notification.requestPermission();
    }
  }

  // 2. Dengarkan Siaran WebSockets
  if (window.Echo && authUserId.value) {
    window.Echo.private(`rpa-progress.${authUserId.value}`)
      .listen('RpaProgressUpdated', (e) => {
        if (e.status === 'completed' || e.status === 'failed') {
            page.props.activeSync = null;
        }
        // Otomatis refresh data (tabel, card summary, activeSync, dan syncHistory) tanpa reload halaman full
        router.reload({ only: ['transaksis', 'rpaSummary', 'activeSync', 'syncHistory'], preserveScroll: true });
      });
      
    window.Echo.private(`data-gas.${authUserId.value}`)
      .listen('DataGasUpdated', (e) => {
        router.reload({ only: ['transaksis', 'rpaSummary', 'syncHistory'], preserveScroll: true });
      });
  }
});

onUnmounted(() => {
  if (window.Echo && authUserId.value) {
    window.Echo.private(`rpa-progress.${authUserId.value}`).stopListening('RpaProgressUpdated');
    window.Echo.private(`data-gas.${authUserId.value}`).stopListening('DataGasUpdated');
  }
  pauseSlideShow();
});
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.text-slate-850 {
  color: rgb(30, 41, 59);
}
.bg-red-650 {
  background-color: rgba(220, 38, 38, 1);
}
</style>
