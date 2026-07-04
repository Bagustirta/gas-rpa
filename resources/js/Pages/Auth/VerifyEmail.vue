<template>
  <div class="min-h-dvh bg-slate-50 flex flex-col justify-center items-center px-4 py-8 relative overflow-hidden font-sans">
    <!-- Background soft glow elements -->
    <div class="absolute w-96 h-96 rounded-full bg-indigo-200/40 blur-[120px] top-1/4 left-1/4 -z-10"></div>
    <div class="absolute w-96 h-96 rounded-full bg-rose-200/30 blur-[120px] bottom-1/4 right-1/4 -z-10"></div>

    <div class="w-full max-w-md">
      <!-- Logo Header -->
      <div class="text-center mb-8">
        <div class="w-24 h-24 flex items-center justify-center mx-auto mb-4">
          <img src="/logo.png" alt="Logo" class="w-full h-full object-contain drop-shadow-md" />
        </div>
        <h2 class="text-2xl font-black tracking-tight text-slate-950">Verifikasi Email</h2>
        <p class="text-xs text-slate-500 mt-1">Satu langkah lagi untuk mulai Trial Anda</p>
      </div>

      <!-- Card Container -->
      <div class="bg-white border border-slate-200/80 rounded-2xl p-8 shadow-xl text-center">
        
        <div class="bg-indigo-50 text-indigo-700 p-4 rounded-xl text-sm leading-relaxed mb-6 border border-indigo-100">
          Terima kasih telah mendaftar! Sebelum memulai masa percobaan, harap verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan ke email Anda. 
          <br><br>
          <span class="font-bold">Silakan periksa folder Inbox atau Spam Anda.</span>
        </div>

        <div v-if="verificationLinkSent" class="mb-6 font-semibold text-sm text-emerald-600 bg-emerald-50 p-3 rounded-xl border border-emerald-100">
          Tautan verifikasi baru telah berhasil dikirim ke alamat email Anda.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
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
              Mengirim...
            </span>
            <span v-else>Kirim Ulang Email Verifikasi</span>
          </button>
        </form>

        <div class="mt-6">
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="text-sm font-medium text-slate-500 hover:text-rose-600 transition-colors"
          >
            Keluar Akun (Log Out)
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>
