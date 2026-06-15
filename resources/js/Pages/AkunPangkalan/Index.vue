<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Akun Pangkalan</h2>
          <p class="text-xs text-slate-500">Kelola akun pangkalan untuk sinkronisasi robot RPA ke portal MyPertamina</p>
        </div>
        <button
          @click="openAddModal"
          class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-sm text-white shadow-md shadow-indigo-100 transition-all duration-200 active:scale-[0.98]"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah Akun</span>
        </button>
      </div>

      <!-- Grid list -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="akun in akuns"
          :key="akun.id"
          class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between hover:border-slate-300 hover:shadow-sm transition-all duration-300 shadow-sm"
        >
          <div>
            <div class="flex items-center justify-between mb-4">
              <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
              </div>
              <div class="flex space-x-1">
                <button
                  @click="openEditModal(akun)"
                  class="p-2 rounded-lg bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 text-slate-500 transition-all duration-200 shadow-sm"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="confirmDelete(akun)"
                  class="p-2 rounded-lg bg-white border border-slate-200 hover:border-red-200 hover:bg-red-50 hover:text-red-600 text-slate-500 transition-all duration-200 shadow-sm"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>

            <h3 class="font-bold text-slate-800 text-base truncate mb-1">{{ akun.nama_pangkalan }}</h3>
            <p class="text-xs text-slate-500 font-mono select-all truncate">{{ akun.email }}</p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Kata Sandi Portal:</span>
            <span class="font-mono bg-slate-50 px-2.5 py-1 rounded border border-slate-200 text-[10px] text-slate-650 uppercase tracking-widest font-bold select-all">
              ••••••••
            </span>
          </div>
        </div>

        <!-- Empty state -->
        <div
          v-if="akuns.length === 0"
          class="col-span-full bg-white border border-slate-200 rounded-2xl py-12 px-6 flex flex-col items-center justify-center text-center shadow-sm"
        >
          <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4 text-xl">
            🔑
          </div>
          <h3 class="font-bold text-slate-850 mb-1">Belum Ada Akun</h3>
          <p class="text-xs text-slate-500 max-w-xs mb-6 font-medium">Silakan tambahkan akun pangkalan MyPertamina baru untuk mulai menggunakan sistem otomasi ini.</p>
          <button
            @click="openAddModal"
            class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-xs font-bold text-slate-600 transition-all duration-200 shadow-sm"
          >
            Tambah Akun Sekarang
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <h3 class="font-bold text-slate-900 text-base mb-6">
              {{ isEditing ? 'Edit Akun Pangkalan' : 'Tambah Akun Pangkalan' }}
            </h3>

            <form @submit.prevent="save" class="space-y-4">
              <!-- Nama Pangkalan -->
              <div>
                <label for="modal-nama" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Nama Pangkalan</label>
                <input
                  id="modal-nama"
                  v-model="form.nama_pangkalan"
                  type="text"
                  required
                  placeholder="Contoh: Pangkalan Gas Barokah"
                  class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                  :class="{ 'border-red-500': form.errors.nama_pangkalan }"
                />
                <p v-if="form.errors.nama_pangkalan" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.nama_pangkalan }}</p>
              </div>

              <!-- Email / Telp -->
              <div>
                <label for="modal-email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Email / No. Telepon Portal</label>
                <input
                  id="modal-email"
                  v-model="form.email"
                  type="text"
                  required
                  placeholder="email@portal.com atau 0812xxxxxxxx"
                  class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                  :class="{ 'border-red-500': form.errors.email }"
                />
                <p v-if="form.errors.email" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.email }}</p>
              </div>

              <!-- Password -->
              <div>
                <label for="modal-password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Kata Sandi Portal</label>
                <input
                  id="modal-password"
                  v-model="form.password"
                  type="text"
                  required
                  placeholder="Kata sandi portal subsiditepat"
                  class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                  :class="{ 'border-red-500': form.errors.password }"
                />
                <p v-if="form.errors.password" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.password }}</p>
              </div>

              <!-- Footer -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100 mt-6">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 hover:text-slate-800 text-xs font-bold text-slate-600 transition-all duration-200"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white shadow-md active:scale-[0.98] transition-all duration-150 disabled:opacity-50"
                >
                  {{ form.processing ? 'Menyimpan...' : 'Simpan Akun' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>

      <!-- Delete Confirmation Modal -->
      <transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white border border-slate-200 rounded-2xl max-w-sm w-full p-6 shadow-2xl">
            <h3 class="font-bold text-slate-900 text-base mb-3 flex items-center space-x-2">
              <span class="text-red-500">⚠️</span>
              <span>Hapus Akun Pangkalan?</span>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-6 font-medium">
              Apakah Anda yakin ingin menghapus akun <strong class="text-slate-800 font-semibold">{{ activeAkun?.nama_pangkalan }}</strong>? Transaksi gas yang terkait dengan akun ini akan ditangguhkan.
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
                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 font-semibold text-xs text-white shadow-md active:scale-[0.98] transition-all duration-150"
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
import { ref, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const page = usePage();
const akuns = computed(() => page.props.akuns);

const showModal = ref(false);
const isEditing = ref(false);
const showDeleteModal = ref(false);
const activeAkun = ref(null);

const form = useForm({
  nama_pangkalan: '',
  email: '',
  password: '',
});

const openAddModal = () => {
  form.reset();
  form.clearErrors();
  isEditing.value = false;
  showModal.value = true;
};

const openEditModal = (akun) => {
  form.clearErrors();
  form.nama_pangkalan = akun.nama_pangkalan;
  form.email = akun.email;
  form.password = akun.password;
  activeAkun.value = akun;
  isEditing.value = true;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const save = () => {
  if (isEditing.value) {
    form.put(route('akun-pangkalan.update', activeAkun.value.id), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('akun-pangkalan.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const confirmDelete = (akun) => {
  activeAkun.value = akun;
  showDeleteModal.value = true;
};

const destroy = () => {
  form.delete(route('akun-pangkalan.destroy', activeAkun.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false;
      activeAkun.value = null;
    },
  });
};
</script>

<style scoped>
.text-slate-850 {
  color: rgb(30, 41, 59);
}
.text-slate-650 {
  color: rgb(71, 85, 105);
}
</style>
