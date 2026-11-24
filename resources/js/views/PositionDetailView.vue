<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiClient } from '../api/client';
import type { Position, CandidateRecommendation } from '../types/ats';
import { CheckCircle2, XCircle, Sparkles, UserPlus, ArrowLeft } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const position = ref<Position | null>(null);
const recommendations = ref<CandidateRecommendation[]>([]);
const loading = ref(true);
const applyingCandidateId = ref<number | null>(null);
const successMessage = ref<string | null>(null);

const loadData = async () => {
  loading.value = true;
  try {
    const [posRes, recRes] = await Promise.all([
      apiClient.get(`/positions/${route.params.id}`),
      apiClient.get(`/positions/${route.params.id}/recommendations`)
    ]);
    position.value = posRes.data.data;
    recommendations.value = recRes.data.data;
  } catch (error) {
    console.error('Error fetching position data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

const applyCandidate = async (candidateId: number) => {
  if (!position.value) return;
  applyingCandidateId.value = candidateId;
  successMessage.value = null;

  try {
    await apiClient.post('/applications', {
      position_id: position.value.id,
      candidate_id: candidateId,
      stage: 'new',
      notes: 'Postulado automáticamente desde el motor de matching.',
    });
    
    successMessage.value = '¡Candidato postulado exitosamente al proceso!';
    setTimeout(() => {
      router.push('/pipeline');
    }, 1200);
  } catch (error: any) {
    alert(error.response?.data?.message || 'Error al postular al candidato.');
  } finally {
    applyingCandidateId.value = null;
  }
};
</script>

<template>
  <div>
    <router-link to="/positions" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-white mb-4">
      <ArrowLeft :size="16" /> Volver a vacantes
    </router-link>

    <div v-if="loading" class="text-center py-12 text-slate-500">Calculando compatibilidad de perfiles...</div>

    <div v-else-if="position">
      <!-- Banner de Éxito -->
      <div v-if="successMessage" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-lg">
        {{ successMessage }}
      </div>

      <!-- Header Posición -->
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 mb-6">
        <span class="text-xs uppercase tracking-wider font-semibold text-sky-400">{{ position.company?.name }}</span>
        <h1 class="text-2xl font-bold text-white mt-1">{{ position.title }}</h1>
        <p class="text-slate-400 text-sm mt-2">{{ position.description }}</p>
      </div>

      <!-- Ranking de Recomendaciones -->
      <div class="flex items-center gap-2 mb-4">
        <Sparkles :size="20" class="text-amber-400" />
        <h2 class="text-lg font-bold text-white">Ranking de Candidatos Compatibles</h2>
      </div>

      <div class="space-y-4">
        <div 
          v-for="cand in recommendations" 
          :key="cand.candidate_id"
          class="bg-slate-900 border border-slate-800 rounded-xl p-5"
        >
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-white">{{ cand.candidate_name }}</h3>
                <span 
                  :class="cand.meets_all_mandatory ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
                  class="px-2.5 py-0.5 text-xs font-semibold rounded-full border"
                >
                  {{ cand.meets_all_mandatory ? 'Cumple Obligatorios' : 'Faltan Obligatorios' }}
                </span>
              </div>
              <p class="text-sm text-slate-400 mt-0.5">{{ cand.email }}</p>
            </div>

            <!-- Score y Acción -->
            <div class="flex items-center gap-6">
              <div class="text-right">
                <span class="text-2xl font-black text-sky-400">{{ cand.overall_score }}%</span>
                <span class="block text-[11px] text-slate-500 uppercase">Match Score</span>
              </div>

              <button
                @click="applyCandidate(cand.candidate_id)"
                :disabled="applyingCandidateId === cand.candidate_id"
                class="bg-sky-600 hover:bg-sky-500 disabled:bg-slate-800 text-white px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-sm"
              >
                <UserPlus :size="15" />
                {{ applyingCandidateId === cand.candidate_id ? 'Postulando...' : 'Sumar al Proceso' }}
              </button>
            </div>
          </div>

          <!-- Breakdown de Requisitos -->
          <div class="mt-4 pt-4 border-t border-slate-800/80 flex flex-wrap gap-2">
            <div 
              v-for="skill in cand.details" 
              :key="skill.skill_id"
              class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-950 border border-slate-800"
              :class="skill.is_met ? 'text-slate-200' : 'text-slate-500 opacity-60'"
            >
              <CheckCircle2 v-if="skill.is_met" :size="13" class="text-emerald-400 shrink-0" />
              <XCircle v-else :size="13" class="text-rose-400 shrink-0" />
              <span>{{ skill.skill_name }} ({{ skill.candidate_years }}a / {{ skill.required_years }}a)</span>
              <span v-if="skill.is_mandatory" class="text-[10px] text-amber-400 font-bold">*</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>