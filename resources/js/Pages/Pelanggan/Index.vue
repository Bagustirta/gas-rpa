<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Data Pelanggan</h2>
          <p class="text-xs text-slate-500">Kelola nomor NIK pelanggan penerima LPG 3 Kg bersubsidi</p>
        </div>
        <div class="flex items-center space-x-2.5">
          <button
            @click="openImportModal"
            class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-sm font-semibold text-slate-650 transition-all duration-200 active:scale-[0.98] shadow-sm"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span>Import Excel</span>
          </button>

          <button
            @click="openAddModal"
            class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-sm text-white shadow-md shadow-indigo-100 transition-all duration-200 active:scale-[0.98]"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah NIK</span>
          </button>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="bg-white border border-slate-200 rounded-2xl p-4 md:p-5 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
        <!-- Search -->
        <div class="relative w-full md:max-w-md">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Cari berdasarkan 16 digit NIK..."
            class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
          />
        </div>

        <!-- Filter Select -->
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          <div class="flex items-center space-x-3 w-full sm:w-auto">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:inline">Status:</span>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="w-full sm:w-44 bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-indigo-500 transition-all duration-200"
            >
              <option value="">Semua Status</option>
              <option value="aktif">Aktif</option>
              <option value="nonaktif">Nonaktif</option>
            </select>
          </div>

          <div class="flex items-center space-x-3 w-full sm:w-auto">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:inline">Sisa Kuota:</span>
            <select
              v-model="filters.sisa_kuota"
              @change="applyFilters"
              class="w-full sm:w-44 bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-indigo-500 transition-all duration-200"
            >
              <option value="">Semua Sisa Kuota</option>
              <option value="4">Sisa 4 (Belum Beli)</option>
              <option value="3">Sisa 3</option>
              <option value="2">Sisa 2</option>
              <option value="1">Sisa 1</option>
              <option value="0">Sisa 0 (Habis)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-xs text-slate-400 uppercase font-bold tracking-wider">
                <th class="py-3 px-4">NIK</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Terpakai Bulan Ini</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr v-for="(pelanggan, index) in localPelanggans" :key="pelanggan.id" class="hover:bg-slate-50/80 transition-colors group">
                <!-- NIK -->
                <td class="py-3.5 px-4 font-mono text-slate-800 tracking-wider">
                  <div class="flex items-center space-x-2">
                    <span>{{ revealedNikMap[pelanggan.id] ? pelanggan.nik : maskNik(pelanggan.nik) }}</span>
                    <button @click="toggleNikShow(pelanggan.id)" class="text-slate-400 hover:text-indigo-600 transition-colors p-0.5" :title="revealedNikMap[pelanggan.id] ? 'Sembunyikan NIK' : 'Tampilkan NIK'">
                      <svg v-if="revealedNikMap[pelanggan.id]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.88 9.88a3 3 0 104.24 4.24m-1.41-5.65L18.36 18.36M1 1l22 22" />
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
                </td>
                <!-- Status -->
                <td class="py-3.5 px-4 text-center">
                  <button
                    @click="toggleStatus(pelanggan)"
                    :class="[
                      'px-2.5 py-1 rounded-lg text-xs font-bold inline-block border transition-all duration-200 hover:scale-105 active:scale-95',
                      pelanggan.status
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : 'bg-red-50 text-red-700 border-red-200'
                    ]"
                  >
                    {{ pelanggan.status ? 'Aktif' : 'Nonaktif' }}
                  </button>
                </td>
                <!-- Quota -->
                <td class="py-3.5 px-4 text-center">
                  <span :class="[
                    'px-2.5 py-1 rounded-lg text-xs font-bold inline-block border',
                    pelanggan.total_tabung_bulan_ini >= 4 ? 'bg-red-50 border-red-200 text-red-700' :
                    pelanggan.total_tabung_bulan_ini >= 2 ? 'bg-orange-50 border-orange-200 text-orange-700' :
                    'bg-emerald-50 border-emerald-200 text-emerald-700'
                  ]">
                    {{ pelanggan.total_tabung_bulan_ini }} / 4
                  </span>
                </td>
                <!-- Actions -->
                <td class="py-3.5 px-4 text-right">
                  <div class="inline-flex space-x-1">
                    <button
                      @click="openEditModal(pelanggan)"
                      class="p-2 rounded-lg bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-500 transition-all duration-200 shadow-sm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(pelanggan)"
                      class="p-2 rounded-lg bg-white border border-slate-200 hover:border-red-200 hover:bg-red-50 hover:text-red-600 text-slate-500 transition-all duration-200 shadow-sm"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="localPelanggans.length === 0">
                <td colspan="4" class="py-10 text-center text-xs text-slate-400 uppercase tracking-widest font-bold">
                  Tidak ada data pelanggan ditemukan
                </td>
              </tr>
            </tbody>
          </table>
          
          <!-- Trigger Loading Data Scroll Mentok Bawah -->
          <div ref="loadMoreTrigger" class="h-14 flex items-center justify-center w-full mt-2">
            <div v-if="isLoadingMore" class="flex items-center space-x-2 text-indigo-600">
              <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-sm font-semibold animate-pulse">Memuat data pelanggan...</span>
            </div>
            <span v-else-if="pelanggans?.next_page_url === null && localPelanggans.length > 0" class="text-xs text-slate-400 font-medium">Semua data telah ditampilkan.</span>
          </div>
        </div>

      </div>

      <!-- Add/Edit Modal -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <h3 class="font-bold text-slate-900 text-base mb-6">
              {{ isEditing ? 'Edit NIK Pelanggan' : 'Tambah Pelanggan Baru' }}
            </h3>

            <form @submit.prevent="save" class="space-y-5">
              <!-- NIK -->
              <div>
                <label for="modal-nik" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">Nomor Induk Kependudukan (NIK)</label>
                <input
                  id="modal-nik"
                  v-model="form.nik"
                  type="text"
                  required
                  maxlength="16"
                  placeholder="Masukkan 16 digit NIK..."
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-semibold placeholder-slate-400 focus:bg-white focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 font-mono tracking-wider shadow-sm transition-all duration-200"
                  :class="{ 'border-red-500 bg-red-50/10': form.errors.nik }"
                />
                <p v-if="form.errors.nik" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.nik }}</p>
                <p class="text-[11px] text-slate-500 mt-1 font-normal">Sisa karakter: {{ 16 - (form.nik ? form.nik.length : 0) }} digit</p>
              </div>

              <!-- Status -->
              <div class="flex items-center justify-between bg-slate-50 p-3.5 rounded-xl border border-slate-300">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">Status Pelanggan Aktif</span>
                <input
                  v-model="form.status"
                  type="checkbox"
                  class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                />
              </div>

              <!-- Footer -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-slate-200">
                <button
                  type="button"
                  @click="showModal = false"
                  class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 hover:text-slate-800 text-xs font-bold text-slate-600 transition-all duration-200"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white shadow-md active:scale-[0.98] transition-all duration-150 disabled:opacity-50"
                >
                  {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>

      <!-- Excel Import Modal -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <h3 class="font-bold text-slate-900 text-base mb-4">Import Pelanggan dari Excel</h3>

            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 text-xs text-indigo-800 leading-relaxed mb-6 font-medium">
              <strong>Format Excel:</strong> Pastikan spreadsheet Anda memiliki kolom bernamakan <strong>nik</strong> (huruf kecil) pada baris pertama. Format kolom sebagai <em>Text</em> agar angka 0 di depan NIK tidak hilang.
            </div>

            <form @submit.prevent="submitImport" class="space-y-5">
              <div>
                <label for="import-file" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Pilih File Excel (.xlsx, .xls, .csv)</label>
                <input
                  id="import-file"
                  type="file"
                  required
                  accept=".xlsx, .xls, .csv"
                  @change="handleFileUpload"
                  class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs text-slate-700 focus:outline-none file:mr-4 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-slate-100 file:text-slate-600 file:hover:bg-slate-200 cursor-pointer"
                />
                <p v-if="importForm.errors.file" class="text-xs text-red-500 mt-1 font-medium">{{ importForm.errors.file }}</p>
              </div>

              <!-- Footer -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
                <button
                  type="button"
                  @click="showImportModal = false"
                  class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 hover:text-slate-800 text-xs font-bold text-slate-600 transition-all duration-200"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="importForm.processing"
                  class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white shadow-md active:scale-[0.98] transition-all duration-150 disabled:opacity-50"
                >
                  {{ importForm.processing ? 'Mengimport...' : 'Mulai Import' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>

      <!-- Delete Confirmation -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-sm w-full p-6 shadow-2xl">
            <h3 class="font-bold text-slate-900 text-base mb-3 flex items-center space-x-2">
              <span class="text-red-500">⚠️</span>
              <span>Hapus Pelanggan?</span>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-6 font-medium">
              Apakah Anda yakin ingin menghapus pelanggan dengan NIK <strong class="text-slate-800 font-mono">{{ activePelanggan?.nik }}</strong>? Tindakan ini tidak dapat dibatalkan.
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
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const page = usePage();
const pelanggans = computed(() => page.props.pelanggans);

const filters = reactive({
  search: page.props.filters?.search || '',
  status: page.props.filters?.status || '',
  sisa_kuota: page.props.filters?.sisa_kuota || '',
});

const pelangganFilters = computed(() => ({
  search: filters.search,
  status: filters.status,
  sisa_kuota: filters.sisa_kuota,
}));

// Infinite Scroll Logic
const localPelanggans = ref([...(pelanggans.value?.data || [])]);
const loadMoreTrigger = ref(null);
const isLoadingMore = ref(false);
let observer = null;

watch(() => pelanggans.value, (newVal) => {
  if (!newVal) return;
  if (newVal.current_page === 1) {
    localPelanggans.value = [...newVal.data];
  } else {
    localPelanggans.value = [...localPelanggans.value, ...newVal.data];
  }
  isLoadingMore.value = false;
}, { deep: true });

const loadMore = () => {
  if (pelanggans.value.next_page_url && !isLoadingMore.value) {
    isLoadingMore.value = true;
    router.get(pelanggans.value.next_page_url, {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['pelanggans'],
    });
  }
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
});

onUnmounted(() => {
  if (observer) observer.disconnect();
});
const showModal = ref(false);
const isEditing = ref(false);
const showImportModal = ref(false);
const showDeleteModal = ref(false);
const activePelanggan = ref(null);

const revealedNikMap = ref({});
const toggleNikShow = (id) => {
  revealedNikMap.value[id] = !revealedNikMap.value[id];
};

const maskNik = (nik) => {
  if (!nik || nik.length < 12) return nik || '-';
  return nik.substring(0, 6) + '******' + nik.substring(12);
};

const form = useForm({
  nik: '',
  status: true,
});

const importForm = useForm({
  file: null,
});

let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 400);
};

const applyFilters = () => {
  router.get(route('pelanggan.index'), {
    search: filters.search,
    status: filters.status,
    sisa_kuota: filters.sisa_kuota,
  }, {
    preserveState: true,
    replace: true,
  });
};

const openAddModal = () => {
  form.reset();
  form.clearErrors();
  form.status = true;
  isEditing.value = false;
  showModal.value = true;
};

const openEditModal = (pelanggan) => {
  form.clearErrors();
  form.nik = pelanggan.nik;
  form.status = pelanggan.status;
  activePelanggan.value = pelanggan;
  isEditing.value = true;
  showModal.value = true;
};

const openImportModal = () => {
  importForm.reset();
  importForm.clearErrors();
  showImportModal.value = true;
};

const handleFileUpload = (e) => {
  importForm.file = e.target.files[0];
};

const save = () => {
  if (isEditing.value) {
    form.put(route('pelanggan.update', activePelanggan.value.id), {
      onSuccess: () => showModal.value = false,
    });
  } else {
    form.post(route('pelanggan.store'), {
      onSuccess: () => showModal.value = false,
    });
  }
};

const toggleStatus = (pelanggan) => {
  router.post(route('pelanggan.toggle-status', pelanggan.id), {}, {
    preserveScroll: true,
  });
};

const submitImport = () => {
  importForm.post(route('pelanggan.import'), {
    onSuccess: () => showImportModal.value = false,
  });
};

const confirmDelete = (pelanggan) => {
  activePelanggan.value = pelanggan;
  showDeleteModal.value = true;
};

const destroy = () => {
  form.delete(route('pelanggan.destroy', activePelanggan.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false;
      activePelanggan.value = null;
    },
  });
};
</script>

<style scoped>
.text-slate-650 {
  color: rgb(71, 85, 105);
}
.bg-red-650 {
  background-color: rgba(220, 38, 38, 1);
}
</style>
