<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import { errorMessage } from '../api/pagination';
import StatePanel from '../components/StatePanel.vue';
import type { DashboardData } from '../types/ats';
import {
  Briefcase, Users, Calendar, TrendingUp,
  Video, ChevronRight
} from 'lucide-vue-next';

const data = ref<DashboardData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const loadDashboard = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get('/dashboard');
    data.value = response.data as DashboardData;
  } catch (caught) {
    error.value = errorMessage(caught, 'No se pudieron cargar las mÃ©tricas.');
    data.value = null;
  } finally {
    loading.value = false;
  }
};

onMounted(loadDashboard);
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard de Reclutamiento</h1>
      <p class="text-slate-500 text-sm">Resumen operativo, embudo de selecciÃ³n y prÃ³ximas reuniones.</p>
    </div>

    <StatePanel
      v-if="loading || error || !data"
      :loading="loading"
      :error="error"
      :empty="!loading && !error && !data"
      loading-label="Cargando mÃ©tricas..."
      empty-label="No hay mÃ©tricas disponibles."
      @retry="loadDashboard"
    />

    <div v-else class="space-y-6">
      <!-- KPIs -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center justify-between shadow-sm">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Vacantes Abiertas</span>
            <div class="text-3xl font-black text-slate-900 mt-1">{{ data.kpis.open_positions }}</div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <Briefcase :size="22" />
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center justify-between shadow-sm">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Candidatos</span>
            <div class="text-3xl font-black text-slate-900 mt-1">{{ data.kpis.total_candidates }}</div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
            <Users :size="22" />
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center justify-between shadow-sm">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">En SelecciÃ³n Activa</span>
            <div class="text-3xl font-black text-slate-900 mt-1">{{ data.kpis.active_applications }}</div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
            <TrendingUp :size="22" />
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center justify-between shadow-sm">
          <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">PrÃ³x. Entrevistas</span>
            <div class="text-3xl font-black text-emerald-600 mt-1">{{ data.kpis.upcoming_interviews }}</div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <Calendar :size="22" />
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Contratados</span>
          <div class="text-3xl font-black text-emerald-700 mt-1">{{ data.kpis.hired_applications }}</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rechazados</span>
          <div class="text-3xl font-black text-rose-600 mt-1">{{ data.kpis.rejected_applications }}</div>
        </div>
      </div>

      <!-- Embudo de Candidaturas -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
          <h2 class="text-base font-bold text-slate-900 mb-4">Embudo de Candidaturas por Etapa</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
              <span class="text-xs text-slate-500 font-semibold uppercase">Nuevos</span>
              <div class="text-2xl font-bold text-slate-800 mt-1">{{ data.stage_distribution.new }}</div>
            </div>
            <div class="bg-sky-50/60 p-4 rounded-xl border border-sky-200">
              <span class="text-xs text-sky-700 font-semibold uppercase">Screening</span>
              <div class="text-2xl font-bold text-sky-800 mt-1">{{ data.stage_distribution.screening }}</div>
            </div>
            <div class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-200">
              <span class="text-xs text-indigo-700 font-semibold uppercase">TÃ©cnica</span>
              <div class="text-2xl font-bold text-indigo-800 mt-1">{{ data.stage_distribution.technical_interview }}</div>
            </div>
            <div class="bg-purple-50/60 p-4 rounded-xl border border-purple-200">
              <span class="text-xs text-purple-700 font-semibold uppercase">Final</span>
              <div class="text-2xl font-bold text-purple-800 mt-1">{{ data.stage_distribution.final_interview }}</div>
            </div>
            <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-200">
              <span class="text-xs text-amber-700 font-semibold uppercase">Oferta</span>
              <div class="text-2xl font-bold text-amber-800 mt-1">{{ data.stage_distribution.offer }}</div>
            </div>
            <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-200">
              <span class="text-xs text-emerald-700 font-semibold uppercase">Contratados</span>
              <div class="text-2xl font-bold text-emerald-800 mt-1">{{ data.stage_distribution.hired }}</div>
            </div>
            <div class="bg-rose-50/60 p-4 rounded-xl border border-rose-200">
              <span class="text-xs text-rose-700 font-semibold uppercase">Rechazados</span>
              <div class="text-2xl font-bold text-rose-800 mt-1">{{ data.stage_distribution.rejected }}</div>
            </div>
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
          <h2 class="text-base font-bold text-slate-900 mb-4">Estado de Postulaciones</h2>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Activas</span><span class="font-bold">{{ data.status_distribution.active }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Contratadas</span><span class="font-bold text-emerald-700">{{ data.status_distribution.hired }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Rechazadas</span><span class="font-bold text-rose-600">{{ data.status_distribution.rejected }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Retiradas</span><span class="font-bold">{{ data.status_distribution.withdrawn }}</span></div>
          </div>
        </div>
      </div>

      <!-- Bloque Doble -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- PrÃ³ximas Entrevistas -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Calendar class="text-indigo-600" :size="18" /> Agenda Inmediata
            </h2>
            <router-link to="/interviews" class="text-xs text-indigo-600 font-semibold hover:underline">Ver todas</router-link>
          </div>

          <div v-if="data.upcoming_interviews.length === 0" class="text-xs text-slate-400 py-6 text-center">
            No hay entrevistas programadas prÃ³ximamente.
          </div>

          <div v-else class="space-y-3">
            <div 
              v-for="intw in data.upcoming_interviews" 
              :key="intw.id"
              class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center justify-between"
            >
              <div>
                <h3 class="text-xs font-bold text-slate-900">
                  {{ intw.application?.candidate?.first_name }} {{ intw.application?.candidate?.last_name }}
                </h3>
                <p class="text-[11px] text-slate-500">{{ intw.application?.position?.title }}</p>
                <span class="text-[10px] text-slate-400 mt-0.5 block">
                  {{ new Date(intw.scheduled_at).toLocaleString() }}
                </span>
              </div>

              <a 
                v-if="intw.location_or_link" 
                :href="intw.location_or_link" 
                target="_blank"
                class="bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded-lg text-xs font-medium transition shadow-sm"
              >
                <Video :size="14" />
              </a>
            </div>
          </div>
        </div>

        <!-- BÃºsquedas Activas -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Briefcase class="text-indigo-600" :size="18" /> BÃºsquedas Activas
            </h2>
            <router-link to="/positions" class="text-xs text-indigo-600 font-semibold hover:underline">Ver todas</router-link>
          </div>

          <div class="space-y-3">
            <div 
              v-for="pos in data.recent_positions" 
              :key="pos.id"
              class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center justify-between"
            >
              <div>
                <h3 class="text-xs font-bold text-slate-900">{{ pos.title }}</h3>
                <p class="text-[11px] text-slate-500">{{ pos.company?.name }} â€¢ {{ pos.seniority }}</p>
              </div>

              <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-indigo-700 bg-indigo-100 px-2.5 py-0.5 rounded-full">
                  {{ pos.applications_count }} candidatos
                </span>
                <router-link :to="`/positions/${pos.id}`" class="text-slate-400 hover:text-slate-700">
                  <ChevronRight :size="16" />
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>