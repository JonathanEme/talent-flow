<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import { errorMessage } from '../api/pagination';
import { usePaginatedList } from '../composables/usePaginatedList';
import { notifyError, notifySuccess } from '../composables/useNotifications';
import PaginationControls from '../components/PaginationControls.vue';
import StatePanel from '../components/StatePanel.vue';
import InterviewFormModal from '../components/InterviewFormModal.vue';
import type { Application, Interview } from '../types/ats';
import { Calendar, CalendarPlus, Video, Star, CheckCircle, X } from 'lucide-vue-next';

const filters = reactive({ status: '', type: '', from: '', to: '' });
const listParams = () => {
  const params: Record<string, string> = {};
  if (filters.status) params.status = filters.status;
  if (filters.type) params.type = filters.type;
  if (filters.from) params.from = filters.from;
  if (filters.to) params.to = filters.to;
  return params;
};

const {
  items: interviews,
  meta,
  links,
  page,
  loading,
  error,
  load,
  goToPage,
} = usePaginatedList<Interview>('/interviews', listParams);

const { items: applications, load: loadApplications } = usePaginatedList<Application>('/applications');
const showCreateModal = ref(false);
const showFeedbackModal = ref(false);
const selectedInterview = ref<Interview | null>(null);
const savingFeedback = ref(false);
const formError = ref<string | null>(null);
const feedbackForm = ref({
  score: 8,
  strengths: '',
  weaknesses: '',
  general_notes: '',
  recommendation: 'approved',
});

const dateFormatter = new Intl.DateTimeFormat('es', { dateStyle: 'medium', timeStyle: 'short' });
const formatDate = (value: string) => dateFormatter.format(new Date(value));

const applyFilters = async () => { page.value = 1; await load(); };
const clearFilters = async () => { filters.status = ''; filters.type = ''; filters.from = ''; filters.to = ''; await applyFilters(); };

onMounted(async () => {
  await Promise.all([load(), loadApplications()]);
});

const handleCreated = async () => {
  notifySuccess('Entrevista agendada con éxito.');
  await load();
};

const openFeedbackModal = (interview: Interview) => {
  selectedInterview.value = interview;
  formError.value = null;
  feedbackForm.value = {
    score: 8,
    strengths: '',
    weaknesses: '',
    general_notes: '',
    recommendation: 'approved',
  };
  showFeedbackModal.value = true;
};

const submitFeedback = async () => {
  if (!selectedInterview.value) return;
  savingFeedback.value = true;
  formError.value = null;

  try {
    await apiClient.post(`/interviews/${selectedInterview.value.id}/feedback`, feedbackForm.value);
    showFeedbackModal.value = false;
    notifySuccess('Evaluación registrada correctamente.');
    await load();
  } catch (caught) {
    formError.value = errorMessage(caught, 'Error al guardar el feedback.');
    notifyError(formError.value);
  } finally {
    savingFeedback.value = false;
  }
};
</script>

<template>
  <div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
          <Calendar class="text-indigo-600" :size="24" /> Agenda de Entrevistas y Rúbricas
        </h1>
        <p class="text-slate-500 text-sm">Monitorea sesiones agendadas y registra feedback de evaluación.</p>
      </div>
      <button
        type="button"
        @click="showCreateModal = true"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm"
      >
        <CalendarPlus :size="16" /> Nueva entrevista
      </button>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
      <select v-model="filters.status" aria-label="Estado entrevista" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm"><option value="">Todos los estados</option><option value="scheduled">Programada</option><option value="completed">Completada</option><option value="canceled">Cancelada</option><option value="no_show">No asistió</option></select>
      <select v-model="filters.type" aria-label="Tipo entrevista" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm"><option value="">Todos los tipos</option><option value="screening">Screening</option><option value="hr">HR</option><option value="technical">Técnica</option><option value="client">Cliente</option><option value="final">Final</option></select>
      <input v-model="filters.from" type="date" aria-label="Desde" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm" />
      <input v-model="filters.to" type="date" aria-label="Hasta" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm" />
      <div class="flex gap-2"><button type="button" @click="applyFilters" class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm">Filtrar</button><button type="button" @click="clearFilters" class="px-3 py-2 rounded-lg bg-slate-100 text-sm">Limpiar</button></div>
    </div>

    <StatePanel
      v-if="loading || error || interviews.length === 0"
      :loading="loading"
      :error="error"
      :empty="interviews.length === 0"
      loading-label="Cargando agenda..."
      empty-label="No hay entrevistas programadas."
      @retry="load"
    />

    <template v-else>
      <div class="space-y-3">
      <div 
        v-for="item in interviews" 
        :key="item.id"
        class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm"
      >
        <div>
          <div class="flex items-center gap-2.5">
            <span class="text-xs uppercase font-bold px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200">
              {{ item.type }}
            </span>
            <span 
              class="text-xs font-semibold px-2 py-0.5 rounded"
              :class="item.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
            >
              {{ item.status === 'completed' ? 'Completada' : 'Pendiente' }}
            </span>
          </div>

          <h2 class="text-lg font-bold text-slate-900 mt-1">
            {{ item.application?.candidate?.first_name }} {{ item.application?.candidate?.last_name }}
          </h2>
          <p class="text-xs text-slate-500">{{ item.application?.position?.title }}</p>

          <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
            <span class="flex items-center gap-1.5">
              <Calendar :size="13" class="text-slate-500" /> {{ formatDate(item.scheduled_at) }}
            </span>
            <a 
              v-if="item.location_or_link" 
              :href="item.location_or_link" 
              target="_blank" 
              class="flex items-center gap-1 text-sky-600 hover:underline"
            >
              <Video :size="13" /> Abrir Sala
            </a>
          </div>
        </div>

        <div class="flex items-center gap-3 self-end md:self-center">
          <div v-if="item.feedbacks?.length" class="text-right">
            <span class="text-xl font-bold text-amber-500 flex items-center gap-1">
              <Star :size="18" fill="currentColor" /> {{ item.feedbacks[0].score }}/10
            </span>
            <span class="text-[10px] text-slate-500 uppercase">{{ item.feedbacks[0].recommendation }}</span>
            <span class="text-[10px] text-slate-500 uppercase">{{ item.feedbacks.length }} evaluación(es)</span>
          </div>

          <button 
            v-else 
            @click="openFeedbackModal(item)"
            class="bg-indigo-600 hover:bg-indigo-500 text-white px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
          >
            <CheckCircle :size="14" /> Cargar Scorecard
          </button>
        </div>
      </div>
      </div>

      <PaginationControls :meta="meta" :links="links" @change="goToPage" />
    </template>

    <!-- Modal Scorecard Feedback -->
    <div v-if="showFeedbackModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white border border-slate-200 rounded-xl max-w-lg w-full p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="feedback-modal-title">
        <div class="flex justify-between items-center mb-4">
          <h3 id="feedback-modal-title" class="text-lg font-bold text-slate-900">Rúbrica de Entrevista</h3>
          <button type="button" aria-label="Cerrar" @click="showFeedbackModal = false" class="text-slate-400 hover:text-slate-600">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="submitFeedback" class="space-y-4 text-xs">
          <div>
            <label class="block text-slate-700 font-medium mb-1">Calificación Global (1 a 10)</label>
            <input type="number" v-model="feedbackForm.score" min="1" max="10" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 font-bold text-base outline-none focus:border-sky-500" />
          </div>

          <div>
            <label class="block text-slate-700 font-medium mb-1">Veredicto</label>
            <select v-model="feedbackForm.recommendation" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900">
              <option value="approved">Aprobado / Recomendado</option>
              <option value="on_hold">En Espera / Dudas</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-700 font-medium mb-1">Fortalezas</label>
            <textarea v-model="feedbackForm.strengths" rows="2" placeholder="Aspectos técnicos y actitudinales destacados..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900"></textarea>
          </div>

          <div>
            <label class="block text-slate-700 font-medium mb-1">Debilidades o Puntos a Mejorar</label>
            <textarea v-model="feedbackForm.weaknesses" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900"></textarea>
          </div>

          <div>
            <label class="block text-slate-700 font-medium mb-1">Notas Generales</label>
            <textarea v-model="feedbackForm.general_notes" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900"></textarea>
          </div>

          <p v-if="formError" class="rounded-lg bg-rose-50 p-2.5 text-xs text-rose-700" role="alert">{{ formError }}</p>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showFeedbackModal = false" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
            <button type="submit" :disabled="savingFeedback" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">
              {{ savingFeedback ? 'Guardando...' : 'Guardar Evaluación' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <InterviewFormModal
      :open="showCreateModal"
      :applications="applications"
      @close="showCreateModal = false"
      @created="handleCreated"
    />
  </div>
</template>