<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import type { Position } from '../types/ats';
import { Building2, MapPin, DollarSign, ChevronRight } from 'lucide-vue-next';

const positions = ref<Position[]>([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const res = await apiClient.get('/positions');
    positions.value = res.data.data.data;
  } catch (error) {
    console.error('Error fetching positions:', error);
  } finally {
    loading.value = false;
  }
});
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

    <div v-if="loading" class="text-center py-12 text-slate-500">Cargando vacantes...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div 
        v-for="pos in positions" 
        :key="pos.id"
        class="bg-slate-900 border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition"
      >
        <div class="flex justify-between items-start">
          <div>
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 mb-2">
              {{ pos.seniority }} • {{ pos.modality }}
            </span>
            <h2 class="text-lg font-bold text-white">{{ pos.title }}</h2>
            <p class="text-sm text-slate-400 flex items-center gap-1.5 mt-1">
              <Building2 :size="14" /> {{ pos.company?.name }}
            </p>
          </div>
          <router-link 
            :to="`/positions/${pos.id}`" 
            class="bg-slate-800 hover:bg-slate-700 p-2 rounded-lg text-slate-300 hover:text-white transition"
          >
            <ChevronRight :size="18" />
          </router-link>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
          <span class="flex items-center gap-1">
            <MapPin :size="13" /> Remoto / Híbrido
          </span>
          <span v-if="pos.salary_min" class="flex items-center gap-1 text-emerald-400 font-medium">
            <DollarSign :size="13" /> ${{ pos.salary_min }} - ${{ pos.salary_max }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>