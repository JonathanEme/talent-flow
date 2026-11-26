<script setup lang="ts">
import { onMounted, reactive } from 'vue';
import { usePaginatedList } from '../composables/usePaginatedList';
import PaginationControls from '../components/PaginationControls.vue';
import StatePanel from '../components/StatePanel.vue';
import type { Position } from '../types/ats';
import { Building2, MapPin, DollarSign, ChevronRight, Search } from 'lucide-vue-next';

const filters = reactive({ search: '', status: '', seniority: '' });
const listParams = () => {
  const params: Record<string, string> = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.seniority) params.seniority = filters.seniority;
  return params;
};

const {
  items: positions,
  meta,
  links,
  page,
  loading,
  error,
  load,
  goToPage,
} = usePaginatedList<Position>('/positions', listParams);

const modalityLabels: Record<string, string> = {
  remote: 'Remoto',
  hybrid: 'Híbrido',
  on_site: 'Presencial',
};

const applyFilters = async () => { page.value = 1; await load(); };
const clearFilters = async () => { filters.search = ''; filters.status = ''; filters.seniority = ''; await applyFilters(); };

onMounted(load);
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Posiciones Abiertas</h1>
        <p class="text-slate-500 text-sm">Gestiona vacantes y matching de candidatos en tiempo real.</p>
      </div>
      <router-link
        to="/positions/create"
        class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition"
      >
        + Nueva Posición
      </router-link>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
      <label class="md:col-span-2 flex items-center gap-2 bg-white border border-slate-300 rounded-lg px-3 py-2 shadow-sm focus-within:ring-2 focus-within:ring-sky-500">
        <Search :size="16" class="text-slate-400" />
        <input v-model="filters.search" @keyup.enter="applyFilters" type="search" aria-label="Buscar posición" placeholder="Buscar por título, descripción o ubicación" class="bg-transparent outline-none text-sm w-full text-slate-700 placeholder:text-slate-400" />
      </label>
      <select v-model="filters.status" aria-label="Filtrar por estado" class="bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 shadow-sm focus:ring-2 focus:ring-sky-500">
        <option value="">Todos los estados</option>
        <option value="draft">Borrador</option><option value="open">Abierta</option><option value="paused">Pausada</option><option value="closed">Cerrada</option>
      </select>
      <div class="flex gap-2">
        <select v-model="filters.seniority" aria-label="Filtrar por seniority" class="bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 shadow-sm w-full focus:ring-2 focus:ring-sky-500">
          <option value="">Toda seniority</option>
          <option value="JR">JR</option><option value="SSR">SSR</option><option value="SR">SR</option><option value="LEAD">LEAD</option>
        </select>
        <button type="button" @click="applyFilters" class="px-3 py-2 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium">Filtrar</button>
        <button type="button" @click="clearFilters" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium">Limpiar</button>
      </div>
    </div>

    <StatePanel
      v-if="loading || error || positions.length === 0"
      :loading="loading"
      :error="error"
      :empty="positions.length === 0"
      loading-label="Cargando vacantes..."
      empty-label="No hay posiciones registradas. Crea la primera vacante para comenzar."
      @retry="load"
    />

    <template v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="pos in positions"
          :key="pos.id"
          class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition shadow-sm"
        >
          <div class="flex justify-between items-start">
            <div>
              <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 mb-2">
                {{ pos.seniority }} • {{ modalityLabels[pos.modality] ?? pos.modality }}
              </span>
              <h2 class="text-lg font-bold text-slate-900">{{ pos.title }}</h2>
              <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-1">
                <Building2 :size="14" /> {{ pos.company?.name }}
              </p>
            </div>
            <router-link
              :to="`/positions/${pos.id}`"
              class="bg-slate-100 hover:bg-slate-200 p-2 rounded-lg text-slate-500 hover:text-slate-700 transition"
              :aria-label="`Ver posición ${pos.title}`"
            >
              <ChevronRight :size="18" />
            </router-link>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-1">
              <MapPin :size="13" />
              {{ modalityLabels[pos.modality] ?? pos.modality }}
              <template v-if="pos.location"> · {{ pos.location }}</template>
            </span>
            <span v-if="pos.salary_min" class="flex items-center gap-1 text-emerald-400 font-medium">
              <DollarSign :size="13" /> ${{ pos.salary_min }} - ${{ pos.salary_max }}
            </span>
          </div>
        </div>
      </div>

      <PaginationControls :meta="meta" :links="links" @change="goToPage" />
    </template>
  </div>
</template>
