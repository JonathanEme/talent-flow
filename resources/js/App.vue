<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  LayoutDashboard, Briefcase, Kanban, Users,
  Building2, Calendar, DollarSign, LogOut, UserRound
} from 'lucide-vue-next';
import { fetchMe, logout, type AuthUser } from './api/auth';
import ToastHost from './components/ToastHost.vue';
import NotificationBell from './components/NotificationBell.vue';

const route = useRoute();
const router = useRouter();
const user = ref<AuthUser | null>(null);
const roleLabel = computed(() => ({
  global_admin: 'Admin global',
  company_admin: 'Admin empresa',
  member: 'Miembro',
})[user.value?.role ?? 'member']);

const syncUser = async (): Promise<void> => {
  user.value = route.path === '/login' ? null : await fetchMe();
};

onMounted(syncUser);
watch(() => route.path, syncUser);

const signOut = async () => {
  await logout();
  user.value = null;
  await router.push('/login');
};
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 text-slate-800">
    <!-- Header Navbar -->
    <header v-if="route.path !== '/login'" class="border-b border-slate-200 bg-white/90 backdrop-blur sticky top-0 z-50 px-6 py-3.5 flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-3">
        <div class="h-9 w-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-md shadow-indigo-600/20">
          TF
        </div>
        <div class="flex flex-col">
          <span class="text-lg font-bold tracking-tight text-slate-900 leading-none">Talent<span class="text-indigo-600">Flow</span></span>
          <span class="text-[10px] text-slate-400 font-medium tracking-wide uppercase mt-0.5">Enterprise ATS</span>
        </div>
      </div>

      <nav class="flex items-center gap-1 text-sm font-medium">
        <router-link 
          to="/" 
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition" 
          active-class="!text-indigo-600 !bg-indigo-50 font-semibold"
        >
          <LayoutDashboard :size="15" /> Dashboard
        </router-link>
        <router-link 
          to="/positions" 
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition" 
          active-class="!text-indigo-600 !bg-indigo-50 font-semibold"
        >
          <Briefcase :size="15" /> Posiciones
        </router-link>
        <router-link 
          to="/pipeline" 
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition" 
          active-class="!text-indigo-600 !bg-indigo-50 font-semibold"
        >
          <Kanban :size="15" /> Pipeline
        </router-link>
        <router-link 
          to="/candidates" 
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition" 
          active-class="!text-indigo-600 !bg-indigo-50 font-semibold"
        >
          <Users :size="15" /> Candidatos
        </router-link>
        <router-link 
          to="/companies" 
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition" 
          active-class="!text-indigo-600 !bg-indigo-50 font-semibold"
        >
          <Building2 :size="15" /> Empresas
        </router-link>
        <router-link 
          to="/interviews" 
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition" 
          active-class="!text-indigo-600 !bg-indigo-50 font-semibold"
        >
          <Calendar :size="15" /> Entrevistas
        </router-link>
        <router-link 
          to="/offers" 
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition" 
          active-class="!text-indigo-600 !bg-indigo-50 font-semibold"
        >
          <DollarSign :size="15" /> Ofertas
        </router-link>
      </nav>

      <div v-if="user" class="flex items-center gap-3 border-l border-slate-200 pl-4">
        <NotificationBell />
        <div class="flex items-center gap-2 text-sm text-slate-600">
          <UserRound :size="16" />
          <span>{{ user.name }}</span>
          <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">{{ roleLabel }}</span>
        </div>
        <button
          type="button"
          class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
          title="Cerrar sesión"
          @click="signOut"
        >
          <LogOut :size="16" />
        </button>
      </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-6 md:p-8">
      <router-view />
    </main>
    <ToastHost />
  </div>
</template>