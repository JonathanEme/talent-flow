<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import { Calendar, Video, Star, CheckCircle, X } from 'lucide-vue-next';

const interviews = ref<any[]>([]);
const loading = ref(true);

// Modal Feedback
const showFeedbackModal = ref(false);
const selectedInterview = ref<any>(null);
const feedbackForm = ref({
  score: 8,
  strengths: '',
  weaknesses: '',
  general_notes: '',
  recommendation: 'approved',
});
const savingFeedback = ref(false);

const loadInterviews = async () => {
  loading.value = true;
  try {
    const res = await apiClient.get('/interviews');
    interviews.value = res.data.data.data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

onMounted(loadInterviews);

const openFeedbackModal = (interview: any) => {
  selectedInterview.value = interview;
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
  try {
    await apiClient.post(`/interviews/${selectedInterview.value.id}/feedback`, {
      score: feedbackForm.value.score,
      strengths: feedbackForm.value.strengths,
      weaknesses: feedbackForm.value.weaknesses,
      general_notes: feedbackForm.value.general_notes,
      recommendation: feedbackForm.value.recommendation,
    });
    showFeedbackModal.value = false;
    loadInterviews();
  } catch (error: any) {
    alert(error.response?.data?.message || 'Error al guardar el feedback.');
  } finally {
    savingFeedback.value = false;
  }
};
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
        <Calendar class="text-sky-400" :size="24" /> Agenda de Entrevistas y Rúbricas
      </h1>
      <p class="text-slate-400 text-sm">Monitorea sesiones agendadas y registra feedback de evaluación.</p>
    </div>

    <div v-if="loading" class="text-center py-16 text-slate-500">Cargando agenda...</div>

    <div v-else class="space-y-3">
      <div 
        v-for="item in interviews" 
        :key="item.id"
        class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4"
      >
        <div>
          <div class="flex items-center gap-2.5">
            <span class="text-xs uppercase font-bold px-2 py-0.5 rounded bg-sky-500/10 text-sky-400 border border-sky-500/20">
              {{ item.type }}
            </span>
            <span 
              class="text-xs font-semibold px-2 py-0.5 rounded"
              :class="item.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'"
            >
              {{ item.status === 'completed' ? 'Completada' : 'Pendiente' }}
            </span>
          </div>

          <h2 class="text-lg font-bold text-white mt-1">
            {{ item.application?.candidate?.first_name }} {{ item.application?.candidate?.last_name }}
          </h2>
          <p class="text-xs text-slate-400">{{ item.application?.position?.title }}</p>

          <div class="flex items-center gap-4 mt-3 text-xs text-slate-400">
            <span class="flex items-center gap-1.5">
              <Calendar :size="13" class="text-slate-500" /> {{ new Date(item.scheduled_at).toLocaleString() }}
            </span>
            <a 
              v-if="item.location_or_link" 
              :href="item.location_or_link" 
              target="_blank" 
              class="flex items-center gap-1 text-sky-400 hover:underline"
            >
              <Video :size="13" /> Abrir Sala
            </a>
          </div>
        </div>

        <div class="flex items-center gap-3 self-end md:self-center">
          <div v-if="item.feedback" class="text-right">
            <span class="text-xl font-bold text-amber-400 flex items-center gap-1">
              <Star :size="18" fill="currentColor" /> {{ item.feedback.score }}/10
            </span>
            <span class="text-[10px] text-slate-500 uppercase">{{ item.feedback.recommendation }}</span>
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

    <!-- Modal Scorecard Feedback -->
    <div v-if="showFeedbackModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-white">Rúbrica de Entrevista</h3>
          <button @click="showFeedbackModal = false" class="text-slate-400 hover:text-white">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="submitFeedback" class="space-y-4 text-xs">
          <div>
            <label class="block text-slate-300 font-medium mb-1">Calificación Global (1 a 10)</label>
            <input type="number" v-model="feedbackForm.score" min="1" max="10" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-bold text-base outline-none focus:border-sky-500" />
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Veredicto</label>
            <select v-model="feedbackForm.recommendation" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
              <option value="approved">Aprobado / Recomendado</option>
              <option value="on_hold">En Espera / Dudas</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Fortalezas</label>
            <textarea v-model="feedbackForm.strengths" rows="2" placeholder="Aspectos técnicos y actitudinales destacados..." class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white"></textarea>
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Debilidades o Puntos a Mejorar</label>
            <textarea v-model="feedbackForm.weaknesses" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white"></textarea>
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Notas Generales</label>
            <textarea v-model="feedbackForm.general_notes" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showFeedbackModal = false" class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300">Cancelar</button>
            <button type="submit" :disabled="savingFeedback" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">
              {{ savingFeedback ? 'Guardando...' : 'Guardar Evaluación' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>