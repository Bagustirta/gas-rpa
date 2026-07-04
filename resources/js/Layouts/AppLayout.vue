<template>
  <div class="h-dvh w-full overflow-hidden bg-slate-50 text-slate-800 flex flex-col md:flex-row font-sans">
    <!-- Mobile Header -->
    <header class="md:hidden bg-white border-b border-slate-200/80 px-4 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">
      <div class="flex items-center space-x-2">
        <div class="w-10 h-10 flex items-center justify-center">
          <img src="/logo.png" alt="Logo" class="w-full h-full object-contain drop-shadow-sm" />
        </div>
        <span class="font-bold text-lg tracking-wider text-indigo-600">GAS-RPA</span>
      </div>
      <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="text-slate-500 hover:text-slate-800 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path v-if="isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </header>

    <!-- Sidebar Navigation -->
    <aside :class="[
      'fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col justify-between transform transition-transform duration-300 md:translate-x-0 md:static',
      isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
    ]">
      <div class="flex-1 overflow-y-auto">
        <!-- Logo Header -->
        <div class="h-16 flex items-center px-6 border-b border-slate-200/80 sticky top-0 bg-white z-10">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 flex items-center justify-center">
              <img src="/logo.png" alt="Logo" class="w-full h-full object-contain drop-shadow-sm" />
            </div>
            <div>
              <h1 class="font-black text-base tracking-tight text-slate-900">GAS-RPA</h1>
              <p class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Distribution Sync</p>
            </div>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-1.5">
          <Link
            v-for="link in navLinks"
            :key="link.route"
            :href="route(link.route)"
            :class="[
              'flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 group',
              isCurrentRoute(link.route)
                ? 'bg-indigo-50 text-indigo-650 border-l-2 border-indigo-600 pl-3.5 shadow-sm'
                : 'text-slate-600 hover:bg-slate-100/70 hover:text-slate-900'
            ]"
            @click="isMobileMenuOpen = false"
          >
            <span class="text-lg" :class="isCurrentRoute(link.route) ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600'">
              <component :is="link.icon" class="w-5 h-5" />
            </span>
            <span>{{ link.label }}</span>
          </Link>
        </nav>
      </div>

      <!-- User Info & Logout -->
      <div class="p-4 border-t border-slate-200 bg-slate-50/50">
        <div class="flex items-center space-x-3 mb-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold uppercase">
            {{ userInitials }}
          </div>
          <div class="overflow-hidden w-full flex justify-between items-center pr-2">
            <div>
              <h4 class="font-bold text-sm text-slate-800 truncate">{{ $page.props.auth.user?.name }}</h4>
              <p class="text-[10px] text-slate-500 truncate font-mono">{{ $page.props.auth.user?.email }}</p>
            </div>
          </div>
        </div>

        <!-- Subscription Status Box -->
        <div v-if="$page.props.auth.user?.role !== 'super_admin'">
          <div v-if="$page.props.auth.user?.subscription_status === 'trial'" class="mb-3 w-full bg-indigo-50 border border-indigo-100 rounded-xl px-3 py-2 flex items-center justify-between">
            <span class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider">Status: Trial</span>
            <span class="text-[10px] font-bold bg-indigo-200 text-indigo-700 px-2 py-0.5 rounded-full">{{ subscriptionDaysRemaining }} Hari</span>
          </div>
          <div v-else-if="$page.props.auth.user?.subscription_status === 'non-aktif'" class="mb-3 w-full bg-red-50 border border-red-100 rounded-xl px-3 py-2 flex items-center justify-between">
            <span class="text-[10px] font-bold text-red-800 uppercase tracking-wider">Status: Berakhir</span>
            <span class="text-[10px] font-bold bg-red-200 text-red-700 px-2 py-0.5 rounded-full">Expired</span>
          </div>
          <div v-else-if="$page.props.auth.user?.subscription_status === 'aktif'" class="mb-3 w-full bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2 flex items-center justify-between">
            <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Status: Premium</span>
            <span class="text-[10px] font-bold bg-emerald-200 text-emerald-700 px-2 py-0.5 rounded-full">{{ subscriptionDaysRemaining }} Hari</span>
          </div>
          
          <!-- Upgrade via Billing Page Button -->
          <Link v-if="$page.props.auth.user?.subscription_status !== 'aktif'" 
             :href="route('billing')" 
             class="mb-3 w-full flex items-center justify-center space-x-2 px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold shadow-sm transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span>Tingkatkan Akun</span>
          </Link>
        </div>

        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-red-50 hover:border-red-200 hover:text-red-600 text-xs font-semibold text-slate-600 shadow-sm transition-all duration-200"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span>Keluar Aplikasi</span>
        </Link>
      </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div
      v-if="isMobileMenuOpen"
      @click="isMobileMenuOpen = false"
      class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm md:hidden"
    ></div>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col w-full min-w-0 min-h-0 overflow-x-hidden">
      <!-- Top navbar (desktop only) -->
      <header class="hidden md:flex h-16 bg-white border-b border-slate-200/80 px-8 items-center justify-between sticky top-0 z-20 shadow-sm w-full">
        <h2 class="font-bold text-lg text-slate-850">{{ currentPageTitle }}</h2>
        <div class="flex items-center space-x-4">
          <div class="h-8 w-[1px] bg-slate-200"></div>
          <div class="text-xs text-right">
            <p class="font-semibold text-slate-600 text-[10px] uppercase tracking-wider">Sesi Aktif</p>
            <p class="text-[11px] text-slate-500 font-mono">{{ currentDate }}</p>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="flex-1 w-full min-h-0 p-4 md:p-8 overflow-y-auto overflow-x-hidden">
        <!-- Toast Notification Area -->
        <transition-group
          enter-active-class="transform ease-out duration-300 transition"
          enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
          enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
          class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"
        >
          <!-- Success Toast -->
          <div
            v-if="toast.show && toast.type === 'success'"
            key="success-toast"
            class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl shadow-lg flex items-center space-x-3 backdrop-blur-md max-w-sm"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs font-semibold">{{ toast.message }}</span>
          </div>

          <!-- Error Toast -->
          <div
            v-if="toast.show && toast.type === 'error'"
            key="error-toast"
            class="bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl shadow-lg flex items-center space-x-3 backdrop-blur-md max-w-sm"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs font-semibold">{{ toast.message }}</span>
          </div>
        </transition-group>

        <!-- Main Slot -->
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// Icon Components
const DashboardIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>`
};

const KeyIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>`
};

const UsersIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>`
};

const TransaksiIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`
};

const isMobileMenuOpen = ref(false);
const page = usePage();

const navLinks = computed(() => {
  const isSuper = page.props.auth.user?.role === 'super_admin';
  
  if (isSuper) {
    return [
      { route: 'superadmin.dashboard', label: 'Monitor Server', icon: DashboardIcon },
      { route: 'users.index', label: 'Manajemen Agen', icon: UsersIcon },
      // Placeholder for CRUD Role / Status routes that will be added
    ];
  }

  // Regular Agent Links
  return [
    { route: 'dashboard', label: 'Dashboard', icon: DashboardIcon },
    { route: 'akun-pangkalan.index', label: 'Akun Pangkalan', icon: KeyIcon },
    { route: 'pelanggan.index', label: 'Data Pelanggan', icon: UsersIcon },
    { route: 'transaksi-gas.index', label: 'Transaksi Gas', icon: TransaksiIcon },
  ];
});

const toast = ref({
  show: false,
  message: '',
  type: 'success'
});

const isCurrentRoute = (routeName) => {
  try {
    return route().current(routeName) || route().current().startsWith(routeName.replace('.index', ''));
  } catch (e) {
    return false;
  }
};

const currentPageTitle = computed(() => {
  const currentLink = navLinks.value.find(link => isCurrentRoute(link.route));
  return currentLink ? currentLink.label : 'Menu';
});

const userInitials = computed(() => {
  const name = page.props.auth.user?.name || 'A';
  return name.split(' ').map(n => n[0]).slice(0, 2).join('');
});

const currentDate = computed(() => {
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return new Date().toLocaleDateString('id-ID', options);
});

const subscriptionDaysRemaining = computed(() => {
  const expiredAt = page.props.auth.user?.subscription_expired_at;
  if (!expiredAt) return 0;
  
  const diffTime = new Date(expiredAt) - new Date();
  const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return days > 0 ? days : 0;
});

// Watch flash notifications
watch(() => page.props.flash, (newFlash) => {
  if (newFlash?.success) {
    showToast(newFlash.success, 'success');
  } else if (newFlash?.error) {
    showToast(newFlash.error, 'error');
  }
}, { deep: true, immediate: true });

const showToast = (message, type = 'success') => {
  toast.value.message = message;
  toast.value.type = type;
  toast.value.show = true;
  
  setTimeout(() => {
    toast.value.show = false;
  }, 4000);
};
</script>

<style scoped>
.text-indigo-650 {
  color: rgb(67, 56, 202);
}
.text-slate-850 {
  color: rgb(30, 41, 59);
}
</style>
