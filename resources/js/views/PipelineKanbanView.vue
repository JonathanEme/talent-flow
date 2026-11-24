<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { apiClient } from '../api/client';
import { errorMessage, unwrapItem } from '../api/pagination';
import { usePaginatedList } from '../composables/usePaginatedList';
import { notifyError, notifySuccess } from '../composables/useNotifications';
import PaginationControls from '../components/PaginationControls.vue';
import StatePanel from '../components/StatePanel.vue';
import type { Application, Position } from '../types/ats';
import { Kanban, ArrowRight, History, CalendarPlus, X } from 'lucide-vue-next';

const {
  items: positions,
  loading: loadingPositions,
  error: positionError,
  load: loadPositions,
} = usePaginatedList<Position>('/positions');

const selectedPositionId = ref<number | null>(null);
const applicationParams = () => (selectedPositionId.value ? { position_id: selectedPositionId.value } : {});
const {
  items: applications,
  meta,
  links,
  page,
  loading: loadingApplications,
  error: applicationError,
  load: loadApplications,
  goToPage,
} = usePaginatedList<Application>('/applications', applicationParams);

const loading = computed(() => loadingPositions.value || loadingApplications.value);
const error = computed(() => positionError.value ?? applicationError.value);

const stages = [
  { key: 'new', label: 'Nuevos', color: 'border-slate-200 bg-slate-100/70', badge: 'bg-slate-200 text-slate-700' },
  { key: 'screening', label: 'Screening', color: 'border-sky-200 bg-sky-50/50', badge: 'bg-sky-100 text-sky-700' },
  { key: 'technical_interview', label: 'Técnica', color: 'border-indigo-200 bg-indigo-50/50', badge: 'bg-indigo-100 text-indigo-700' },
  { key: 'final_interview', label: 'Final', color: 'border-purple-200 bg-purple-50/50', badge: 'bg-purple-100 text-purple-700' },
  { key: 'offer', label: 'Oferta', color: 'border-emerald-200 bg-emerald-50/50', badge: 'bg-emerald-100 text-emerald-700' },
  { key: 'hired', label: 'Contratados', color: 'border-emerald-300 bg-emerald-50', badge: 'bg-emerald-200 text-emerald-800' },
  { key: 'rejected', label: 'Descartados', color: 'border-rose-200 bg-rose-50/50', badge: 'bg-rose-100 text-rose-700' },
];

const groupedApplications = computed(() => {
  const grouped = new Map<string, Application[]>(stages.map((stage) => [stage.key, []]));

  for (const application of applications.value) {
    const bucket = grouped.get(application.stage) ?? [];
    bucket.push(application);
    grouped.set(application.stage, bucket);
  }

  return grouped;
});

const getApplicationsByStage = (stageKey: string) => groupedApplications.value.get(stageKey) ?? [];

onMounted(async () => {
  await loadPositions();
  if (!selectedPositionId.value && positions.value.length > 0) {
    selectedPositionId.value = positions.value[0].id;
  }
  await loadApplications();
});

const filterByPosition = async () => {
  page.value = 1;
  await loadApplications();
};

const reloadAll = async () => {
  await loadPositions();
  await loadApplications();
};

const pendingAppId = ref<number | null>(null);

const moveStage = async (app: Application, nextStage: string) => {
  if (pendingAppId.value !== null) return;
  pendingAppId.value = app.id;

  try {
    const response = await apiClient.patch(`/applications/${app.id}/stage`, {
      stage: nextStage,
      comment: `Movido a ${nextStage} desde tablero Kanban.`,
    });
    const updated = unwrapItem<Application>(response as { data: { data: Application } });
    app.stage = updated.stage;
    notifySuccess(`Candidato movido a ${nextStage}.`);
  } catch (caught) {
    notifyError(errorMessage(caught, 'No se pudo mover la candidatura.'));
  } finally {
    pendingAppId.value = null;
  }
};

const showInterviewModal = ref(false);
const selectedAppForInterview = ref<Application | null>(null);
const savingInterview = ref(false);
const formError = ref<string | null>(null);
const interviewForm = ref({
  type: 'technical',
  scheduled_at: '',
  duration_minutes: 45,
  location_or_link: '',
  notes: '',
});

const openInterviewModal = (app: Application) => {
  selectedAppForInterview.value = app;
  formError.value = null;
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  tomorrow.setHours(10, 0, 0, 0);
  interviewForm.value.scheduled_at = tomorrow.toISOString().slice(0, 16);
  showInterviewModal.value = true;
};

const scheduleInterview = async () => {
  if (!selectedAppForInterview.value) return;

  if (!interviewForm.value.location_or_link.trim()) {
    formError.value = 'Indica el enlace o ubicación de la entrevista.';
    notifyError(formError.value);
    return;
  }

  savingInterview.value = true;
  formError.value = null;

  try {
    await apiClient.post('/interviews', {
      application_id: selectedAppForInterview.value.id,
      type: interviewForm.value.type,
      scheduled_at: interviewForm.value.scheduled_at,
      duration_minutes: interviewForm.value.duration_minutes,
      location_or_link: interviewForm.value.location_or_link,
      notes: interviewForm.value.notes,
    });
    showInterviewModal.value = false;
    notifySuccess('Entrevista agendada con éxito.');
  } catch (caught) {
    formError.value = errorMessage(caught, 'Error al agendar la entrevista.');
    notifyError(formError.value);
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

    <StatePanel
      v-if="loading || error || applications.length === 0"
      :loading="loading"
      :error="error"
      :empty="applications.length === 0"
      loading-label="Cargando pipeline..."
      empty-label="No hay candidaturas para la vacante seleccionada."
      @retry="reloadAll"
    />

    <template v-else>
      <div class="grid grid-cols-1 md:grid-cols-7 gap-4 overflow-x-auto pb-4">
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
                    :aria-label="`Agendar entrevista con ${app.candidate?.first_name}`"
                  >
                    <CalendarPlus :size="15" />
                  </button>
                  <router-link
                    :to="`/applications/${app.id}/timeline`"
                    class="p-1 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                    :aria-label="`Ver timeline de ${app.candidate?.first_name}`"
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
                :disabled="pendingAppId !== null"
                class="text-[11px] bg-sky-50 text-sky-700 hover:bg-sky-100 disabled:opacity-50 border border-sky-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Screening <ArrowRight :size="11" />
              </button>
              <button 
                v-if="stg.key === 'screening'"
                @click="moveStage(app, 'technical_interview')"
                :disabled="pendingAppId !== null"
                class="text-[11px] bg-indigo-50 text-indigo-700 hover:bg-indigo-100 disabled:opacity-50 border border-indigo-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Técnica <ArrowRight :size="11" />
              </button>
              <button 
                v-if="stg.key === 'technical_interview'"
                @click="moveStage(app, 'final_interview')"
                :disabled="pendingAppId !== null"
                class="text-[11px] bg-purple-50 text-purple-700 hover:bg-purple-100 disabled:opacity-50 border border-purple-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Final <ArrowRight :size="11" />
              </button>
              <button 
                v-if="stg.key === 'final_interview'"
                @click="moveStage(app, 'offer')"
                :disabled="pendingAppId !== null"
                class="text-[11px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 disabled:opacity-50 border border-emerald-200 px-2.5 py-1 rounded-lg flex items-center gap-1 font-semibold transition"
              >
                Oferta <ArrowRight :size="11" />
              </button>
            </div>
          </div>
        </div>
      </div>
      </div>

      <PaginationControls :meta="meta" :links="links" @change="goToPage" />
    </template>

    <!-- Modal Entrevista -->
    <div v-if="showInterviewModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="interview-modal-title">
        <div class="flex justify-between items-center mb-4">
          <h3 id="interview-modal-title" class="text-base font-bold text-slate-900">Agendar Entrevista</h3>
          <button type="button" aria-label="Cerrar" @click="showInterviewModal = false" class="text-slate-400 hover:text-slate-600">
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
            <input type="url" v-model="interviewForm.location_or_link" required placeholder="https://meet.google.com/..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>

          <p v-if="formError" class="rounded-lg bg-rose-50 p-2.5 text-xs text-rose-700" role="alert">{{ formError }}</p>

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