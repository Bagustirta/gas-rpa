<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Transaksi Gas LPG 3Kg</h2>
          <p class="text-xs text-slate-500">Catatan transaksi penjualan tabung gas melon bersubsidi</p>
        </div>
        <div class="flex items-center flex-wrap gap-2.5">
          <!-- Reset Sync Button (Visible only when activeSync is true) -->
          <button
            v-if="page.props.activeSync"
            @click="resetSync"
            class="inline-flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 font-semibold text-xs text-white shadow-md shadow-rose-100 transition-all duration-200 active:scale-[0.98]"
            title="Reset antrean sinkronisasi yang macet"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Reset Sync</span>
          </button>

          <!-- Audit Quota Button -->
          <button
            @click="auditQuota"
            class="inline-flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 font-semibold text-xs text-white shadow-md shadow-sky-100 transition-all duration-200 active:scale-[0.98]"
            title="Cek Kuota Pertamina (Audit)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Audit Kuota</span>
          </button>

          <!-- Mulai Sync Pertamina (RPA) -->
          <button
            @click="triggerRpa"
            class="inline-flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 font-semibold text-xs text-white shadow-md shadow-amber-100 transition-all duration-200 active:scale-[0.98]"
            :class="{ 'opacity-50 cursor-not-allowed': page.props.activeSync }"
            :disabled="page.props.activeSync"
            title="Mulai Sync Pertamina (RPA)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 11H19.21" />
            </svg>
            <span>Sync (RPA)</span>
          </button>

          <!-- Distribusi Otomatis -->
          <button
            @click="openDistModal"
            class="inline-flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 font-semibold text-xs text-white shadow-md shadow-emerald-100 transition-all duration-200 active:scale-[0.98]"
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
            class="inline-flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs transition-all duration-200 active:scale-[0.98]"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span>Lapor Massal ({{ selectedIds.length }})</span>
          </button>
        </div>
      </div>

      <!-- RPA Progress Card (Mobile Optimized) -->
      <div v-if="page.props.activeSync" class="bg-indigo-600 rounded-2xl p-4 md:p-5 shadow-lg shadow-indigo-200 text-white relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <!-- SVG background pattern -->
        <div class="absolute inset-0 opacity-10 pointer-events-none">
          <svg width="100%" height="100%" fill="none"><pattern id="pattern-circles" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="2" fill="currentColor"></circle></pattern><rect x="0" y="0" width="100%" height="100%" fill="url(#pattern-circles)"></rect></svg>
        </div>
        <div class="relative z-10 w-full md:w-auto text-center md:text-left">
           <h3 class="font-bold text-lg text-white mb-0.5">Progres RPA Pertamina</h3>
           <p class="text-indigo-200 text-xs font-medium">Bulan berjalan ({{ new Date().toLocaleString('id-ID', { month: 'long', year: 'numeric' }) }})</p>
        </div>
        <div class="relative z-10 flex items-center justify-between md:justify-end gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 hide-scrollbar">
           <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl p-3 flex flex-col items-center min-w-[80px]">
              <span class="text-2xl font-black text-emerald-400 drop-shadow-sm">{{ rpaSummary.sukses }}</span>
              <span class="text-[10px] uppercase tracking-wider font-semibold text-indigo-100 mt-1">Sukses</span>
           </div>
           <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl p-3 flex flex-col items-center min-w-[80px]">
              <span class="text-2xl font-black text-amber-300 drop-shadow-sm animate-pulse">{{ rpaSummary.belum_lapor }}</span>
              <span class="text-[10px] uppercase tracking-wider font-semibold text-indigo-100 mt-1">Antre</span>
           </div>
           <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl p-3 flex flex-col items-center min-w-[80px]">
              <span class="text-2xl font-black text-rose-400 drop-shadow-sm">{{ rpaSummary.gagal }}</span>
              <span class="text-[10px] uppercase tracking-wider font-semibold text-indigo-100 mt-1">Gagal</span>
           </div>
           <div class="flex-1 md:flex-none bg-indigo-700/50 border border-indigo-500/50 rounded-xl p-3 flex flex-col items-center min-w-[80px]">
              <span class="text-2xl font-black text-white drop-shadow-sm">{{ rpaSummary.total }}</span>
              <span class="text-[10px] uppercase tracking-wider font-semibold text-indigo-100 mt-1">Total</span>
           </div>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="bg-white border border-slate-200 rounded-2xl p-4 md:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center shadow-sm">
        <!-- Search NIK -->
        <div class="relative w-full">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Cari NIK..."
            class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
          />
        </div>

        <!-- Filter Status Lapor -->
        <div class="w-full">
          <select
            v-model="filters.status_lapor"
            @change="applyFilters"
            class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-indigo-500 transition-all duration-200"
          >
            <option value="">Semua Status Lapor</option>
            <option value="belum_lapor">Belum Lapor</option>
            <option value="proses">Proses Antrean</option>
            <option value="sukses">Sukses Dilaporkan</option>
            <option value="gagal">Gagal Dilaporkan</option>
          </select>
        </div>

        <!-- Filter Pangkalan -->
        <div class="w-full">
          <select
            v-model="filters.akun_pangkalan_id"
            @change="applyFilters"
            class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-indigo-500 transition-all duration-200"
          >
            <option value="">Semua Pangkalan</option>
            <option v-for="p in pangkalans" :key="p.id" :value="p.id">
              {{ p.nama_pangkalan }}
            </option>
          </select>
        </div>

        <!-- Filter Tanggal -->
        <div class="relative w-full">
          <input
            v-model="filters.tanggal"
            @change="applyFilters"
            type="date"
            class="w-full bg-white border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-indigo-500 transition-all duration-200"
            title="Filter Tanggal Transaksi"
          />
          <button
            v-if="filters.tanggal"
            @click="clearTanggalFilter"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
            title="Hapus filter tanggal"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
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
                v-for="t in transaksis.data"
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
                    <span class="font-mono text-slate-800 tracking-wider select-all">{{ t.pelanggan_nik }}</span>
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
                      t.status_lapor === 'gagal' ? 'bg-red-50 text-red-700 border-red-200' :
                      t.status_lapor === 'proses' ? 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse' :
                      'bg-slate-100 text-slate-500 border-slate-200'
                    ]">
                      {{ t.status_lapor.replace('_', ' ') }}
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
              <tr v-if="transaksis.data.length === 0">
                <td colspan="7" class="py-10 text-center text-xs text-slate-400 uppercase tracking-widest font-bold">
                  Tidak ada data transaksi ditemukan
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination (Full Windowed) -->
        <div v-if="transaksis.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6 pt-4 border-t border-slate-100">
          <p class="text-xs text-slate-500 font-medium order-2 sm:order-1">
            Halaman <span class="font-bold text-slate-700">{{ transaksis.current_page }}</span> dari {{ transaksis.last_page }}
            <span class="text-slate-300 mx-1">·</span>
            {{ transaksis.total }} transaksi
          </p>
          <nav class="flex items-center gap-1 order-1 sm:order-2" aria-label="Pagination">
            <template v-for="item in transaksiPages" :key="item">
              <span
                v-if="item === '...'"
                class="inline-flex items-end justify-center h-8 w-5 text-sm text-slate-400 pb-0.5"
              >···</span>
              <Link
                v-else
                :href="route('transaksi-gas.index', { ...currentFilters, page: item })"
                :class="[
                  'inline-flex items-center justify-center h-8 w-8 rounded-md text-xs font-medium border transition-all duration-150',
                  item === transaksis.current_page
                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm shadow-indigo-100 scale-105'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600 shadow-sm'
                ]"
              >{{ item }}</Link>
            </template>
          </nav>
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
                <label for="dist-pangkalan" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Akun Pangkalan</label>
                <select
                  id="dist-pangkalan"
                  v-model="distForm.akun_pangkalan_id"
                  required
                  class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-indigo-500 transition-all duration-200"
                  :class="{ 'border-red-500': distForm.errors.akun_pangkalan_id }"
                >
                  <option value="">Pilih Pangkalan...</option>
                  <option v-for="p in pangkalans" :key="p.id" :value="p.id">
                    {{ p.nama_pangkalan }}
                  </option>
                </select>
                <p v-if="distForm.errors.akun_pangkalan_id" class="text-xs text-red-500 mt-1 font-medium">{{ distForm.errors.akun_pangkalan_id }}</p>
              </div>

              <!-- Total Tabung -->
              <div>
                <label for="dist-total" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Total Tabung yang Diterima</label>
                <input
                  id="dist-total"
                  v-model.number="distForm.total_tabung"
                  type="number"
                  min="1"
                  required
                  placeholder="Masukkan jumlah tabung..."
                  class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                  :class="{ 'border-red-500': distForm.errors.total_tabung }"
                />
                <p v-if="distForm.errors.total_tabung" class="text-xs text-red-500 mt-1 font-medium">{{ distForm.errors.total_tabung }}</p>
                <p class="text-[10px] text-slate-400 mt-1 font-medium leading-relaxed">Sistem akan mengalokasikan tabung ini ke pelanggan secara otomatis berdasarkan sisa kuota mereka.</p>
              </div>

              <!-- Tanggal Transaksi -->
              <div>
                <label for="dist-tanggal" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Tanggal Transaksi</label>
                <input
                  id="dist-tanggal"
                  v-model="distForm.tanggal_transaksi"
                  type="date"
                  required
                  class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-indigo-500 transition-all duration-200"
                  :class="{ 'border-red-500': distForm.errors.tanggal_transaksi }"
                />
                <p v-if="distForm.errors.tanggal_transaksi" class="text-xs text-red-500 mt-1 font-medium">{{ distForm.errors.tanggal_transaksi }}</p>
                <p class="text-[10px] text-slate-400 mt-1 font-medium leading-relaxed">Transaksi akan diutamakan pada minggu dari tanggal ini, lalu didistribusikan ke minggu lain pada bulan yang sama jika kuota minggu ini habis.</p>
              </div>

              <!-- Footer -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100 mt-6">
                <button
                  type="button"
                  @click="showDistModal = false"
                  class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 hover:text-slate-800 text-xs font-bold text-slate-600 transition-all duration-200"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="distForm.processing"
                  class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 font-semibold text-xs text-white shadow-md active:scale-[0.98] transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ distForm.processing ? 'Memproses...' : 'Mulai Distribusi' }}
                </button>
              </div>
            </form>
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
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 11H19.21" />
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
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { usePage, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const page = usePage();
const authUserId = computed(() => page.props.auth.user.id);
const transaksis = computed(() => page.props.transaksis);
const rpaSummary = computed(() => page.props.rpaSummary || { total: 0, sukses: 0, belum_lapor: 0, gagal: 0 });
const pangkalans = computed(() => page.props.pangkalans);

const filters = reactive({
  search: page.props.filters?.search || '',
  status_lapor: page.props.filters?.status_lapor || '',
  akun_pangkalan_id: page.props.filters?.akun_pangkalan_id || '',
  tanggal: page.props.filters?.tanggal || '',
});

// Current filters for building page URLs
const currentFilters = computed(() => ({
  search: filters.search,
  status_lapor: filters.status_lapor,
  akun_pangkalan_id: filters.akun_pangkalan_id,
  tanggal: filters.tanggal,
}));

// Full windowed pagination — always shows 5 pages in the window
const transaksiPages = computed(() => {
  const current = transaksis.value.current_page;
  const last = transaksis.value.last_page;
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
  // Near start: pages 1-5 ... last
  if (current <= 4) {
    return [1, 2, 3, 4, 5, '...', last];
  }
  // Near end: 1 ... last-4 to last
  if (current >= last - 3) {
    return [1, '...', last - 4, last - 3, last - 2, last - 1, last];
  }
  // Middle: 1 ... current-2 current-1 current current+1 current+2 ... last
  return [1, '...', current - 2, current - 1, current, current + 1, current + 2, '...', last];
});

const showDeleteModal = ref(false);
const showDistModal = ref(false);
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
  return transaksis.value.data.length > 0 && selectedIds.value.length === transaksis.value.data.length;
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = transaksis.value.data.map(t => t.id);
  }
};

const clearTanggalFilter = () => {
  filters.tanggal = '';
  applyFilters();
};

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
    tanggal: filters.tanggal,
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
    message: 'Ini akan otomatis membuka terminal di PC Anda dan menjalankan bot untuk audit/pencocokan kuota pelanggan bulan berjalan ke portal Pertamina.',
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
  if (window.Echo && authUserId.value) {
    window.Echo.private(`rpa-progress.${authUserId.value}`)
      .listen('RpaProgressUpdated', (e) => {
        // Otomatis refresh data (tabel dan card summary) tanpa reload halaman full
        router.reload({ only: ['transaksis', 'rpaSummary', 'activeSync'], preserveScroll: true });
      });
  }
});

onUnmounted(() => {
  if (window.Echo && authUserId.value) {
    window.Echo.leave(`rpa-progress.${authUserId.value}`);
  }
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
