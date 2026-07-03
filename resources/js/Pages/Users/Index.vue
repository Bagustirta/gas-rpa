<template>
  <AppLayout>
    <Head title="Manajemen Agen" />

    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-800 tracking-tight">Manajemen Agen</h2>
          <p class="text-sm text-slate-500 mt-1">Kelola akses akun untuk agen/pangkalan lain.</p>
        </div>
        <button
          @click="openModal('add')"
          class="inline-flex items-center justify-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-sm shadow-indigo-200"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah Agen Baru</span>
        </button>
      </div>

      <!-- Data Table -->
      <div class="bg-white border border-slate-200/60 shadow-sm rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200/60">
                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Agen</th>
                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Email / Login</th>
                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Hak Akses</th>
                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Jml Pangkalan</th>
                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Terdaftar Sejak</th>
                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="users.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                  Belum ada agen yang terdaftar.
                </td>
              </tr>
              <tr
                v-for="user in users"
                :key="user.id"
                class="hover:bg-slate-50/50 transition-colors group"
              >
                <td class="py-4 px-6">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs uppercase">
                      {{ user.name.substring(0, 2) }}
                    </div>
                    <span class="font-semibold text-slate-700">{{ user.name }}</span>
                  </div>
                </td>
                <td class="py-4 px-6 text-sm text-slate-600 font-mono">{{ user.email }}</td>
                <td class="py-4 px-6 text-center">
                  <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="user.role === 'super_admin' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'">
                    {{ user.role === 'super_admin' ? 'Super Admin' : 'Agen Biasa' }}
                  </span>
                </td>
                <td class="py-4 px-6 text-center">
                  <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="py-4 px-6 text-center">
                  <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold"
                    :class="user.akun_pangkalans_count > 0 ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-500'">
                    {{ user.akun_pangkalans_count || 0 }}
                  </span>
                </td>
                <td class="py-4 px-6 text-sm text-slate-500">{{ formatDate(user.created_at) }}</td>
                <td class="py-4 px-6 text-right space-x-2">
                  <button
                    @click="openModal('edit', user)"
                    class="text-slate-400 hover:text-indigo-600 transition-colors p-1"
                    title="Edit Data"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button
                    @click="confirmDelete(user)"
                    class="text-slate-400 hover:text-red-600 transition-colors p-1"
                    title="Hapus Akun"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Form Agen -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

      <!-- Modal Panel -->
      <div class="bg-white rounded-2xl shadow-xl transform transition-all sm:w-full sm:max-w-lg relative z-10 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h3 class="text-lg font-bold text-slate-800">
            {{ modalMode === 'add' ? 'Tambah Agen Baru' : 'Edit Data Agen' }}
          </h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent="submitForm" class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Nama Agen</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                :class="{'border-red-500': form.errors.name}"
                placeholder="Misal: Budi Santoso"
                required
              />
              <div v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</div>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Alamat Email (Login)</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                :class="{'border-red-500': form.errors.email}"
                placeholder="Misal: budi@gmail.com"
                required
              />
              <div v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Hak Akses</label>
                <select
                  v-model="form.role"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-500 transition-all duration-200"
                >
                  <option value="agen">Agen Biasa</option>
                  <option value="super_admin">Super Admin</option>
                </select>
                <div v-if="form.errors.role" class="mt-1 text-xs text-red-500">{{ form.errors.role }}</div>
              </div>
              
              <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Status Akun</label>
                <select
                  v-model="form.is_active"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-500 transition-all duration-200"
                >
                  <option :value="true">Aktif (Bisa Login)</option>
                  <option :value="false">Nonaktif (Diblokir)</option>
                </select>
                <div v-if="form.errors.is_active" class="mt-1 text-xs text-red-500">{{ form.errors.is_active }}</div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Password Login</label>
              <input
                v-model="form.password"
                type="password"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                :class="{'border-red-500': form.errors.password}"
                :placeholder="modalMode === 'edit' ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 8 karakter'"
                :required="modalMode === 'add'"
              />
              <div v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</div>
              <p v-if="modalMode === 'edit'" class="mt-1.5 text-[10px] text-slate-400">Kosongkan field ini jika tidak ingin mengubah password agen tersebut.</p>
            </div>
            
            <div v-if="form.password">
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Konfirmasi Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                placeholder="Ulangi password di atas"
                :required="!!form.password"
              />
            </div>
          </div>

          <div class="mt-8 flex justify-end space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
              :disabled="form.processing"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2.5 rounded-xl bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200 flex items-center"
              :disabled="form.processing"
            >
              <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              {{ modalMode === 'add' ? 'Simpan Agen' : 'Update Data' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
  users: Array,
});

const isModalOpen = ref(false);
const modalMode = ref('add'); // 'add' or 'edit'
const selectedUser = ref(null);

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'agen',
  is_active: true,
});

const openModal = (mode, user = null) => {
  modalMode.value = mode;
  selectedUser.value = user;
  
  if (mode === 'edit' && user) {
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.is_active = user.is_active === 1 || user.is_active === true;
    form.password = '';
    form.password_confirmation = '';
  } else {
    form.reset();
    form.role = 'agen';
    form.is_active = true;
  }
  
  form.clearErrors();
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  setTimeout(() => form.reset(), 200);
};

const submitForm = () => {
  if (modalMode.value === 'add') {
    form.post(route('users.store'), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.put(route('users.update', selectedUser.value.id), {
      onSuccess: () => closeModal(),
    });
  }
};

const confirmDelete = (user) => {
  if (confirm(`Apakah Anda yakin ingin menghapus akun agen "${user.name}"? Ini tidak dapat dibatalkan.`)) {
    router.delete(route('users.destroy', user.id));
  }
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};
</script>
