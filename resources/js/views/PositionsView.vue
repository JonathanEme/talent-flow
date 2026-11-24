<script setup lang="ts">
import { onMounted } from 'vue';
import { usePaginatedList } from '../composables/usePaginatedList';
import PaginationControls from '../components/PaginationControls.vue';
import StatePanel from '../components/StatePanel.vue';
import type { Position } from '../types/ats';
import { Building2, MapPin, DollarSign, ChevronRight } from 'lucide-vue-next';

const {
  items: positions,
  meta,
  links,
  loading,
  error,
  load,
  goToPage,
} = usePaginatedList<Position>('/positions');

const modalityLabels: Record<string, string> = {
  remote: 'Remoto',
  hybrid: 'Híbrido',
  on_site: 'Presencial',
};

onMounted(load);
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Posiciones Abiertas</h1>
        <p class="text-slate-400 text-sm">Gestiona vacantes y matching de candidatos en tiempo real.</p>
      </div>
      <router-link
        to="/positions/create"
        class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition"
      >
        + Nueva Posición
      </router-link>
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
          class="bg-slate-900 border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition"
        >
          <div class="flex justify-between items-start">
            <div>
              <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 mb-2">
                {{ pos.seniority }} • {{ modalityLabels[pos.modality] ?? pos.modality }}
              </span>
              <h2 class="text-lg font-bold text-white">{{ pos.title }}</h2>
              <p class="text-sm text-slate-400 flex items-center gap-1.5 mt-1">
                <Building2 :size="14" /> {{ pos.company?.name }}
              </p>
            </div>
            <router-link
              :to="`/positions/${pos.id}`"
              class="bg-slate-800 hover:bg-slate-700 p-2 rounded-lg text-slate-300 hover:text-white transition"
              :aria-label="`Ver posición ${pos.title}`"
            >
              <ChevronRight :size="18" />
            </router-link>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
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
