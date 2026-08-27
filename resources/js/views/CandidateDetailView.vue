<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { apiClient } from '../api/client';
import { Mail, Phone, MapPin, DollarSign, ArrowLeft, History, ExternalLink, Briefcase } from 'lucide-vue-next';

const route = useRoute();
const candidate = ref<any>(null);
const loading = ref(true);

onMounted(async () => {
  try {
    const res = await apiClient.get(`/candidates/${route.params.id}`);
    candidate.value = res.data.data;
  } catch (error) {
    console.error('Error al cargar detalle del candidato:', error);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <router-link to="/candidates" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 mb-6 font-medium transition">
      <ArrowLeft :size="16" /> Volver a candidatos
    </router-link>

    <div v-if="loading" class="text-center py-16 text-slate-400">Cargando ficha del candidato...</div>

    <div v-else-if="candidate" class="space-y-6">
      <!-- Encabezado de Perfil -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ candidate.first_name }} {{ candidate.last_name }}</h1>
            <p v-if="candidate.summary" class="text-sm text-slate-500 mt-1 max-w-xl">{{ candidate.summary }}</p>
          </div>

          <div v-if="candidate.salary_expectation" class="text-right">
            <span class="text-xl font-bold text-emerald-600 flex items-center gap-1">
              <DollarSign :size="18" /> ${{ Number(candidate.salary_expectation).toLocaleString() }}
            </span>
            <span class="text-[10px] text-slate-400 font-semibold uppercase">Pretensión Salarial</span>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-600">
          <span class="flex items-center gap-1.5"><Mail :size="14" class="text-slate-400" /> {{ candidate.email }}</span>
          <span v-if="candidate.phone" class="flex items-center gap-1.5"><Phone :size="14" class="text-slate-400" /> {{ candidate.phone }}</span>
          <span v-if="candidate.location" class="flex items-center gap-1.5"><MapPin :size="14" class="text-slate-400" /> {{ candidate.location }}</span>
          <a v-if="candidate.linkedin_url" :href="candidate.linkedin_url" target="_blank" class="text-indigo-600 hover:underline flex items-center gap-1 font-medium">
            <ExternalLink :size="14" /> LinkedIn
          </a>
        </div>
      </div>

      <!-- Historial de Postulaciones -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
          <Briefcase :size="18" class="text-indigo-600" /> Postulaciones Activas e Históricas
        </h2>

        <div v-if="!candidate.applications || candidate.applications.length === 0" class="text-xs text-slate-400 py-4 text-center">
          Este candidato aún no participa en ningún proceso de selección.
        </div>

        <div v-else class="space-y-3">
          <div 
            v-for="app in candidate.applications" 
            :key="app.id"
            class="bg-slate-50 border border-slate-200 p-4 rounded-xl flex items-center justify-between"
          >
            <div>
              <h3 class="text-sm font-bold text-slate-900">{{ app.position?.title }}</h3>
              <p class="text-xs text-slate-500">{{ app.position?.company?.name }} • Postulado el {{ app.applied_at }}</p>
              <span class="inline-block mt-2 text-[11px] bg-indigo-100 text-indigo-700 font-semibold px-2.5 py-0.5 rounded-full uppercase">
                Etapa: {{ app.stage }}
              </span>
            </div>

            <router-link 
              :to="`/applications/${app.id}/timeline`" 
              class="bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 px-3.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
            >
              <History :size="14" /> Ver Trazabilidad
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>