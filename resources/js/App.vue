<script setup lang="ts">
import { 
  LayoutDashboard, Briefcase, Kanban, Users, 
  Building2, Calendar, DollarSign 
} from 'lucide-vue-next';
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 text-slate-800">
    <!-- Header Navbar -->
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur sticky top-0 z-50 px-6 py-3.5 flex items-center justify-between shadow-xs">
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
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-6 md:p-8">
      <router-view />
    </main>
  </div>
</template>