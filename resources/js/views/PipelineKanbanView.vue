<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import type { Position, Application } from '../types/ats';
import { Kanban, ArrowRight, History, CalendarPlus, X } from 'lucide-vue-next';

const positions = ref<Position[]>([]);
const selectedPositionId = ref<number | null>(null);
const applications = ref<Application[]>([]);
const loading = ref(true);

// Modal Entrevista
const showInterviewModal = ref(false);
const selectedAppForInterview = ref<Application | null>(null);
const interviewForm = ref({
  type: 'technical',
  scheduled_at: '',
  duration_minutes: 45,
  location_or_link: '',
  notes: '',
});
const savingInterview = ref(false);

const stages = [
  { key: 'new', label: 'Nuevos', color: 'border-slate-200 bg-slate-100/70', badge: 'bg-slate-200 text-slate-700' },
  { key: 'screening', label: 'Screening', color: 'border-sky-200 bg-sky-50/50', badge: 'bg-sky-100 text-sky-700' },
  { key: 'technical_interview', label: 'Técnica', color: 'border-indigo-200 bg-indigo-50/50', badge: 'bg-indigo-100 text-indigo-700' },
  { key: 'final_interview', label: 'Final', color: 'border-purple-200 bg-purple-50/50', badge: 'bg-purple-100 text-purple-700' },
  { key: 'offer', label: 'Oferta', color: 'border-emerald-200 bg-emerald-50/50', badge: 'bg-emerald-100 text-emerald-700' },
];

const loadData = async () => {
  loading.value = true;
  try {
    const [posRes, appRes] = await Promise.all([
      apiClient.get('/positions'),
      apiClient.get('/applications', {
        params: selectedPositionId.value ? { position_id: selectedPositionId.value } : {}
      })
    ]);
    positions.value = posRes.data.data.data;
    applications.value = appRes.data.data.data;
    
    if (!selectedPositionId.value && positions.value.length > 0) {
      selectedPositionId.value = positions.value[0].id;
    }
  } catch (error) {
    console.error('Error loading kanban:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

const filterByPosition = async () => {
  loading.value = true;
  try {
    const res = await apiClient.get('/applications', {
      params: selectedPositionId.value ? { position_id: selectedPositionId.value } : {}
    });
    applications.value = res.data.data.data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const getApplicationsByStage = (stageKey: string) => {
  return applications.value.filter(app => app.stage === stageKey);
};

const moveStage = async (app: Application, nextStage: string) => {
  try {
    await apiClient.patch(`/applications/${app.id}/stage`, {
      stage: nextStage,
      user_id: 1,
      comment: `Movido a ${nextStage} desde tablero Kanban.`,
    });
    app.stage = nextStage as any;
  } catch (error) {
    console.error('Error moving stage:', error);
  }
};

const openInterviewModal = (app: Application) => {
  selectedAppForInterview.value = app;
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  tomorrow.setHours(10, 0, 0, 0);
  interviewForm.value.scheduled_at = tomorrow.toISOString().slice(0, 16);
  showInterviewModal.value = true;
};

const scheduleInterview = async () => {
  if (!selectedAppForInterview.value) return;
  savingInterview.value = true;
  try {
    await apiClient.post('/interviews', {
      application_id: selectedAppForInterview.value.id,
      interviewer_id: 2,
      type: interviewForm.value.type,
      scheduled_at: interviewForm.value.scheduled_at,
      duration_minutes: interviewForm.value.duration_minutes,
      location_or_link: interviewForm.value.location_or_link || 'https://meet.google.com/abc-demo',
      notes: interviewForm.value.notes,
    });
    showInterviewModal.value = false;
    alert('¡Entrevista agendada con éxito!');
  } catch (error: any) {
    alert(error.response?.data?.message || 'Error al agendar entrevista.');
  } finally {
    savingInterview.value = false;
  }
};
</script>

<template>
  <div>
    <!-- Header & Filtro -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
          <Kanban class="text-indigo-600" :size="24" /> Pipeline de Selección
        </h1>
        <p class="text-slate-500 text-sm">Gestiona etapas, entrevistas y trazabilidad en tiempo real.</p>
      </div>

      <div class="flex items-center gap-3">
        <label class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Vacante:</label>
        <select 
          v-model="selectedPositionId" 
          @change="filterByPosition"
          class="bg-white border border-slate-300 text-slate-800 text-sm rounded-lg px-3.5 py-2 shadow-xs outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-medium"
        >
          <option :value="null">Todas las posiciones</option>
          <option v-for="pos in positions" :key="pos.id" :value="pos.id">
            {{ pos.title }}
          </option>
        </select>
      </div>
    </div>

    <!-- Kanban Columns -->
    <div v-if="loading" class="text-center py-16 text-slate-400">Cargando pipeline...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-5 gap-4 overflow-x-auto pb-4">
      <div 
        v-for="stg in stages" 
        :key="stg.key" 
        class="border rounded-2xl p-3.5 flex flex-col min-h-[540px] shadow-xs"
        :class="stg.color"
      >
        <div class="flex justify-between items-center mb-3">
          <h2 class="font-bold text-xs text-slate-700 uppercase tracking-wide">{{ stg.label }}</h2>
          <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="stg.badge">
            {{ getApplicationsByStage(stg.key).length }}
          </span>
        </div>

        <!-- Cards -->
        <div class="space-y-3 flex-1">
          <div 
            v-for="app in getApplicationsByStage(stg.key)" 
            :key="app.id"
            class="bg-white border border-slate-200/80 rounded-xl p-4 shadow-xs hover:shadow-md hover:border-slate-300 transition flex flex-col justify-between gap-3"
          >
            <div>
              <div class="flex justify-between items-start">
                <h3 class="font-bold text-sm text-slate-900">{{ app.candidate?.first_name }} {{ app.candidate?.last_name }}</h3>
                <div class="flex items-center gap-1">
                  <button 
                    @click="openInterviewModal(app)" 
                    class="p-1 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition" 
                    title="Agendar Entrevista"
                  >
                    <CalendarPlus :size="15" />
                  </button>
                  <router-link 
                    :to="`/applications/${app.id}/timeline`" 
                    class="p-1 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                    title="Ver Timeline"
                  >
                    <History :size="15" />
                  </router-link>
                </div>
              </div>
              <p class="text-xs text-slate-500 mt-0.5 truncate">{{ app.candidate?.email }}</p>
              <span class="inline-block mt-2 text-[11px] bg-slate-100 text-slate-700 font-medium px-2 py-0.5 rounded-md truncate max-w-full">
                {{ app.position?.title }}
              </span>
            </div>

            <!-- Movimiento de Etapa -->
            <div class="pt-2.5 border-t border-slate-100 flex justify-end gap-1.5">
              <button 
                v-if="stg.key === 'new'" 
                @click="moveStage(app, 'screening')" 
                class="text-[11px] bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Screening <ArrowRight :size="11" />
              </button>
              <button 
                v-if="stg.key === 'screening'" 
                @click="moveStage(app, 'technical_interview')" 
                class="text-[11px] bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Técnica <ArrowRight :size="11" />
              </button>
              <button 
                v-if="stg.key === 'technical_interview'" 
                @click="moveStage(app, 'final_interview')" 
                class="text-[11px] bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Final <ArrowRight :size="11" />
              </button>
              <button 
                v-if="stg.key === 'final_interview'" 
                @click="moveStage(app, 'offer')" 
                class="text-[11px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Oferta <ArrowRight :size="11" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Entrevista -->
    <div v-if="showInterviewModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-base font-bold text-slate-900">Agendar Entrevista</h3>
          <button @click="showInterviewModal = false" class="text-slate-400 hover:text-slate-600">
            <X :size="18" />
          </button>
        </div>

        <p class="text-xs text-slate-500 mb-4">
          Candidato: <span class="text-slate-800 font-bold">{{ selectedAppForInterview?.candidate?.first_name }} {{ selectedAppForInterview?.candidate?.last_name }}</span>
        </p>

        <form @submit.prevent="scheduleInterview" class="space-y-4 text-xs">
          <div>
            <label class="block text-slate-700 font-semibold mb-1">Tipo de Entrevista</label>
            <select v-model="interviewForm.type" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="screening">Screening RRHH</option>
              <option value="technical">Entrevista Técnica</option>
              <option value="final">Entrevista Final / Cliente</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-700 font-semibold mb-1">Fecha y Hora</label>
            <input type="datetime-local" v-model="interviewForm.scheduled_at" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div>
            <label class="block text-slate-700 font-semibold mb-1">Duración (minutos)</label>
            <input type="number" v-model="interviewForm.duration_minutes" min="15" max="180" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div>
            <label class="block text-slate-700 font-semibold mb-1">Link de Reunión</label>
            <input type="url" v-model="interviewForm.location_or_link" placeholder="https://meet.google.com/..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showInterviewModal = false" class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">Cancelar</button>
            <button type="submit" :disabled="savingInterview" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-xs">
              {{ savingInterview ? 'Guardando...' : 'Confirmar Entrevista' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>