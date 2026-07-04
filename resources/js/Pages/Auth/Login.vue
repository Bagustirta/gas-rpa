<template>
  <div class="min-h-dvh bg-slate-50 flex flex-col justify-center items-center px-4 relative overflow-hidden font-sans">
    <!-- Background soft glow elements -->
    <div class="absolute w-96 h-96 rounded-full bg-indigo-200/40 blur-[120px] top-1/4 left-1/4 -z-10"></div>
    <div class="absolute w-96 h-96 rounded-full bg-rose-200/30 blur-[120px] bottom-1/4 right-1/4 -z-10"></div>

    <div class="w-full max-w-md">
      <!-- Logo Header -->
      <div class="text-center mb-8">
        <div class="w-24 h-24 flex items-center justify-center mx-auto mb-4">
          <img src="/logo.png" alt="Logo" class="w-full h-full object-contain drop-shadow-md" />
        </div>
        <h2 class="text-2xl font-black tracking-tight text-slate-950">GAS-RPA</h2>
        <p class="text-xs text-slate-500 mt-1">Automated LPG 3Kg Distribution Sync System</p>
      </div>

      <!-- Card Container -->
      <div class="bg-white border border-slate-200/80 rounded-2xl p-8 shadow-xl">
        <h3 class="text-lg font-bold text-slate-800 mb-6">Masuk ke Dashboard</h3>

        <form @submit.prevent="submit" class="space-y-5">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Alamat Email</label>
            <div class="relative">
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                placeholder="nama@pangkalan.com"
                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition-all duration-200"
                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.email }"
              />
            </div>
            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.email }}</p>
          </div>

          <!-- Password Field -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Kata Sandi</label>
            </div>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition-all duration-200"
                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.password }"
              />
            </div>
            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.password }}</p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
            />
            <label for="remember" class="ml-2 text-xs text-slate-600 select-none">Ingat saya di perangkat ini</label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold text-sm py-3 rounded-xl hover:from-indigo-500 hover:to-violet-500 focus:outline-none shadow-md shadow-indigo-200 active:scale-[0.98] transition-all duration-150 disabled:opacity-50 disabled:pointer-events-none"
          >
            <span v-if="form.processing" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Memproses...
            </span>
            <span v-else>Masuk Sekarang</span>
          </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500">
          Belum punya akun? 
          <Link :href="route('register')" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
            Daftar Gratis (Trial 7 Hari)
          </Link>
        </div>
      </div>

      <!-- Footer Info -->
      <p class="text-center text-[10px] text-slate-400 mt-8">
        &copy; 2026 Pangkalan LPG 3Kg. All rights reserved.<br>
        Otomasi Robot Process Automation (RPA) Sync.
      </p>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
