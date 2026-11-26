<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { apiClient } from '../api/client';
import type { TimelineEvent } from '../types/ats';
import { History, Calendar, Star, ArrowLeft } from 'lucide-vue-next';

const route = useRoute();
const timeline = ref<TimelineEvent[]>([]);
const meta = ref<{ candidate: string; position: string; current_stage: string } | null>(null);
const loading = ref(true);

onMounted(async () => {
  try {
    const res = await apiClient.get(`/applications/${route.params.id}/timeline`);
    meta.value = {
      candidate: res.data.candidate,
      position: res.data.position,
      current_stage: res.data.current_stage,
    };
    timeline.value = res.data.timeline;
  } catch (error) {
    console.error('Error fetching timeline:', error);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="max-w-3xl mx-auto">
    <router-link to="/pipeline" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 mb-6">
      <ArrowLeft :size="16" /> Volver al tablero
    </router-link>

    <div v-if="loading" class="text-center py-12 text-slate-500">Cargando trazabilidad...</div>

    <div v-else-if="meta">
      <div class="bg-white border border-slate-200 rounded-xl p-6 mb-8 shadow-sm">
        <span class="text-xs font-semibold text-sky-600 uppercase tracking-wider">Historial de Candidatura</span>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ meta.candidate }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ meta.position }} • <span class="text-slate-700 font-semibold uppercase">Etapa: {{ meta.current_stage }}</span></p>
      </div>

      <!-- Timeline visual -->
      <div class="relative border-l border-slate-200 ml-4 space-y-8 pb-8">
        <div 
          v-for="(event, idx) in timeline" 
          :key="idx" 
          class="relative pl-6"
        >
          <!-- Bullet Icon -->
          <div 
            class="absolute -left-3 top-1 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
            :class="event.type === 'interview_feedback' ? 'bg-amber-500 text-white' : 'bg-sky-500 text-white'"
          >
            <Star v-if="event.type === 'interview_feedback'" :size="12" />
            <Calendar v-else-if="event.type === 'interview_scheduled'" :size="12" />
            <History v-else :size="12" />
          </div>

          <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <div class="flex justify-between items-start gap-4">
              <h3 class="text-sm font-bold text-slate-900">{{ event.title }}</h3>
              <span class="text-xs text-slate-500">{{ new Date(event.timestamp).toLocaleString() }}</span>
            </div>

            <p v-if="event.details.comment" class="text-xs text-slate-600 mt-2">
              "{{ event.details.comment }}"
            </p>

            <p v-if="event.details.notes" class="text-xs text-slate-600 mt-2 bg-slate-50 p-2 rounded border border-slate-200">
              Notas evaluador: {{ event.details.notes }}
            </p>

            <div v-if="event.details.actor || event.details.evaluator" class="text-[11px] text-slate-500 mt-2">
              Registrado por: <span class="text-slate-700 font-medium">{{ event.details.actor || event.details.evaluator }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>