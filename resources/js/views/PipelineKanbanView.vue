<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClient } from '../api/client';
import { errorMessage, unwrapItem } from '../api/pagination';
import { usePaginatedList } from '../composables/usePaginatedList';
import { notifyError, notifySuccess } from '../composables/useNotifications';
import PaginationControls from '../components/PaginationControls.vue';
import StatePanel from '../components/StatePanel.vue';
import InterviewFormModal from '../components/InterviewFormModal.vue';
import type { Application, Position } from '../types/ats';
import { Kanban, ArrowRight, History, CalendarPlus, X } from 'lucide-vue-next';

const route = useRoute();

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
] as const;

type StageKey = Application['stage'];

const allowedTransitions: Record<StageKey, StageKey[]> = {
  new: ['screening', 'rejected'],
  screening: ['new', 'technical_interview', 'rejected'],
  technical_interview: ['screening', 'final_interview', 'rejected'],
  final_interview: ['technical_interview', 'offer', 'rejected'],
  offer: ['final_interview', 'hired', 'rejected'],
  hired: ['offer'],
  rejected: ['new'],
};

const canDrop = (from: StageKey | null, to: StageKey): boolean =>
  from !== null && from !== to && allowedTransitions[from].includes(to);

const groupedApplications = computed(() => {
  const grouped = new Map<string, Application[]>(stages.map((stage) => [stage.key, []]));

  for (const application of applications.value) {
    const bucket = grouped.get(application.stage) ?? [];
    bucket.push(application);
    grouped.set(application.stage, bucket);
  }

  return grouped;
});

const getApplicationsByStage = (stageKey: StageKey) => groupedApplications.value.get(stageKey) ?? [];

const highlightedAppId = ref<number | null>(null);
const highlightedApplication = computed(
  () => applications.value.find((item) => item.id === highlightedAppId.value) ?? null,
);

const focusHighlightedApplication = async () => {
  if (highlightedAppId.value === null) return;

  await nextTick();
  document.getElementById(`application-${highlightedAppId.value}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

onMounted(async () => {
  const requestedPositionId = Number(route.query.position);
  const requestedApplicationId = Number(route.query.application);

  await loadPositions();
  const requestedPosition = positions.value.find((item) => item.id === requestedPositionId);
  selectedPositionId.value = requestedPosition?.id ?? positions.value[0]?.id ?? null;

  if (Number.isInteger(requestedApplicationId) && requestedApplicationId > 0) {
    highlightedAppId.value = requestedApplicationId;
  }

  await loadApplications();
  await focusHighlightedApplication();
});

const filterByPosition = async () => {
  highlightedAppId.value = null;
  page.value = 1;
  await loadApplications();
};

const reloadAll = async () => {
  await loadPositions();
  await loadApplications();
};

const pendingAppId = ref<number | null>(null);

const moveStage = async (app: Application, nextStage: StageKey) => {
  if (pendingAppId.value !== null) return;
  pendingAppId.value = app.id;

  try {
    const response = await apiClient.patch(`/applications/${app.id}/stage`, {
      stage: nextStage,
      comment: `Movido a ${nextStage} desde tablero Kanban.`,
    });
    const updated = unwrapItem<Application>(response as { data: { data: Application } });
    app.stage = updated.stage;
    app.status = updated.status;
    notifySuccess(`Candidato movido a ${nextStage}.`);
  } catch (caught) {
    notifyError(errorMessage(caught, 'No se pudo mover la candidatura.'));
  } finally {
    pendingAppId.value = null;
  }
};

const draggedAppId = ref<number | null>(null);
const draggingStage = ref<StageKey | null>(null);
const dropTargetStage = ref<StageKey | null>(null);

const resetDrag = () => {
  draggedAppId.value = null;
  draggingStage.value = null;
  dropTargetStage.value = null;
};

const onDragStart = (event: DragEvent, app: Application) => {
  if (pendingAppId.value !== null) {
    event.preventDefault();
    return;
  }

  draggedAppId.value = app.id;
  draggingStage.value = app.stage;

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(app.id));
  }
};

const onDragOver = (event: DragEvent, stageKey: StageKey) => {
  if (!canDrop(draggingStage.value, stageKey)) return;

  event.preventDefault();
  dropTargetStage.value = stageKey;

  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
};

const onDragLeave = (stageKey: StageKey) => {
  if (dropTargetStage.value === stageKey) {
    dropTargetStage.value = null;
  }
};

const onDrop = async (event: DragEvent, stageKey: StageKey) => {
  const fromStage = draggingStage.value;

  if (!canDrop(fromStage, stageKey)) {
    resetDrag();
    return;
  }

  event.preventDefault();

  const appId = Number(event.dataTransfer?.getData('text/plain') ?? draggedAppId.value);
  const app = applications.value.find((item) => item.id === appId);

  resetDrag();

  if (app) {
    await moveStage(app, stageKey);
  }
};

const showInterviewModal = ref(false);
const selectedAppForInterview = ref<Application | null>(null);

const openInterviewModal = (app: Application) => {
  selectedAppForInterview.value = app;
  showInterviewModal.value = true;
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
        <p class="text-slate-400 text-xs mt-1">Arrastra una tarjeta hacia adelante o hacia atrás entre etapas permitidas.</p>
      </div>

      <div class="flex items-center gap-3">
        <label class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Vacante:</label>
        <select 
          v-model="selectedPositionId" 
          @change="filterByPosition"
          class="bg-white border border-slate-300 text-slate-800 text-sm rounded-lg px-3.5 py-2 shadow-sm outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-medium"
        >
          <option :value="null">Todas las posiciones</option>
          <option v-for="pos in positions" :key="pos.id" :value="pos.id">
            {{ pos.title }}
          </option>
        </select>
      </div>
    </div>

    <div
      v-if="highlightedApplication"
      class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800"
    >
      <span>
        Candidato seleccionado desde el ranking:
        <strong>{{ highlightedApplication.candidate?.first_name }} {{ highlightedApplication.candidate?.last_name }}</strong>.
        Arrástralo a la etapa deseada.
      </span>
      <button
        type="button"
        @click="highlightedAppId = null"
        class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 hover:text-indigo-900"
      >
        <X :size="14" /> Quitar selección
      </button>
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
        class="border rounded-2xl p-3.5 flex flex-col min-h-[540px] shadow-sm transition"
        :class="[
          stg.color,
          canDrop(draggingStage, stg.key)
            ? dropTargetStage === stg.key
              ? 'ring-2 ring-indigo-500 bg-indigo-100/80'
              : 'ring-2 ring-dashed ring-indigo-300'
            : '',
        ]"
        @dragover="onDragOver($event, stg.key)"
        @dragleave="onDragLeave(stg.key)"
        @drop="onDrop($event, stg.key)"
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
            :id="`application-${app.id}`"
            :draggable="pendingAppId === null"
            class="bg-white border border-slate-200/80 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-slate-300 transition flex flex-col justify-between gap-3 cursor-grab active:cursor-grabbing select-none"
            :class="[
              draggedAppId === app.id ? 'opacity-50 ring-2 ring-indigo-400' : '',
              highlightedAppId === app.id ? 'ring-2 ring-amber-400 border-amber-300' : '',
            ]"
            @dragstart="onDragStart($event, app)"
            @dragend="resetDrag"
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

    <InterviewFormModal
      :open="showInterviewModal"
      :application-id="selectedAppForInterview?.id ?? null"
      @close="showInterviewModal = false"
      @created="notifySuccess('Entrevista agendada con éxito.')"
    />
  </div>
</template>