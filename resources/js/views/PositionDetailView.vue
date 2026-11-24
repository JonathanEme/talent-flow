<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiClient } from '../api/client';
import { errorMessage, unwrapItem } from '../api/pagination';
import { notifyError } from '../composables/useNotifications';
import StatePanel from '../components/StatePanel.vue';
import type { Position, CandidateRecommendation } from '../types/ats';
import { CheckCircle2, XCircle, Sparkles, UserPlus, ArrowLeft } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const position = ref<Position | null>(null);
const recommendations = ref<CandidateRecommendation[]>([]);
const loading = ref(true);
const positionError = ref<string | null>(null);
const recommendationsError = ref<string | null>(null);
const applyingCandidateId = ref<number | null>(null);
const successMessage = ref<string | null>(null);

const stageLabels: Record<string, string> = {
  new: 'Nuevo',
  screening: 'Screening',
  technical_interview: 'Entrevista técnica',
  final_interview: 'Entrevista final',
  offer: 'Oferta',
  hired: 'Contratado',
  rejected: 'Descartado',
};

const loadPosition = async () => {
  try {
    const response = await apiClient.get(`/positions/${route.params.id}`);
    position.value = unwrapItem<Position>(response as { data: { data: Position } });
  } catch (error) {
    position.value = null;
    positionError.value = errorMessage(error, 'No se pudo cargar la vacante.');
  }
};

const loadRecommendations = async () => {
  recommendationsError.value = null;
  try {
    const response = await apiClient.get(`/positions/${route.params.id}/recommendations`, { params: { limit: 15 } });
    recommendations.value = (response.data.data ?? []) as CandidateRecommendation[];
  } catch (error) {
    recommendations.value = [];
    recommendationsError.value = errorMessage(error, 'No se pudo calcular el ranking de candidatos.');
  }
};

const loadData = async () => {
  loading.value = true;
  positionError.value = null;
  await Promise.all([loadPosition(), loadRecommendations()]);
  loading.value = false;
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
  } catch (error) {
    notifyError(errorMessage(error, 'Error al postular al candidato.'));
  } finally {
    applyingCandidateId.value = null;
  }
};
</script>

<template>
  <div>
    <router-link to="/positions" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 mb-4">
      <ArrowLeft :size="16" /> Volver a vacantes
    </router-link>

    <StatePanel
      v-if="loading || positionError || !position"
      :loading="loading"
      :error="positionError"
      :empty="!loading && !positionError && !position"
      loading-label="Calculando compatibilidad de perfiles..."
      empty-label="No se encontró la vacante solicitada."
      @retry="loadData"
    />

    <template v-else>
      <div v-if="successMessage" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">
        {{ successMessage }}
      </div>

      <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
        <span class="text-xs uppercase tracking-wider font-semibold text-sky-600">{{ position.company?.name }}</span>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ position.title }}</h1>
        <p class="text-slate-500 text-sm mt-2">{{ position.description }}</p>
      </div>

      <div class="flex items-center gap-2 mb-4">
        <Sparkles :size="20" class="text-amber-500" />
        <h2 class="text-lg font-bold text-slate-900">Ranking de Candidatos Compatibles</h2>
      </div>

      <StatePanel
        v-if="recommendationsError || recommendations.length === 0"
        :loading="false"
        :error="recommendationsError"
        :empty="!recommendationsError && recommendations.length === 0"
        loading-label="Calculando compatibilidad de perfiles..."
        empty-label="No hay candidatos compatibles disponibles para esta vacante."
        @retry="loadRecommendations"
      />

      <div v-else class="space-y-4">
        <div
          v-for="cand in recommendations"
          :key="cand.candidate_id"
          class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm"
        >
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-slate-900">{{ cand.candidate_name }}</h3>
                <span
                  :class="cand.meets_all_mandatory ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                  class="px-2.5 py-0.5 text-xs font-semibold rounded-full border"
                >
                  {{ cand.meets_all_mandatory ? 'Cumple Obligatorios' : 'Faltan Obligatorios' }}
                </span>
                <span
                  v-if="cand.already_applied"
                  class="px-2.5 py-0.5 text-xs font-semibold rounded-full border bg-indigo-50 text-indigo-700 border-indigo-200"
                >
                  En proceso · {{ cand.application_stage ? (stageLabels[cand.application_stage] ?? cand.application_stage) : 'postulado' }}
                </span>
              </div>
              <p class="text-sm text-slate-500 mt-0.5">{{ cand.email }}</p>
            </div>

            <div class="flex items-center gap-6">
              <div class="text-right">
                <span class="text-2xl font-black text-sky-600">{{ cand.overall_score }}%</span>
                <span class="block text-[11px] text-slate-500 uppercase">Match Score</span>
              </div>

              <button
                @click="applyCandidate(cand.candidate_id)"
                :disabled="cand.already_applied || applyingCandidateId === cand.candidate_id"
                class="bg-sky-600 hover:bg-sky-500 disabled:bg-slate-300 text-white px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-sm"
              >
                <CheckCircle2 v-if="cand.already_applied" :size="15" />
                <UserPlus v-else :size="15" />
                {{ cand.already_applied ? 'Ya en el proceso' : applyingCandidateId === cand.candidate_id ? 'Postulando...' : 'Sumar al Proceso' }}
              </button>
            </div>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-200 flex flex-wrap gap-2">
            <div
              v-for="skill in cand.details"
              :key="skill.skill_id"
              class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-50 border border-slate-200"
              :class="skill.is_met ? 'text-slate-700' : 'text-slate-400'"
            >
              <CheckCircle2 v-if="skill.is_met" :size="13" class="text-emerald-500 shrink-0" />
              <XCircle v-else :size="13" class="text-rose-500 shrink-0" />
              <span>{{ skill.skill_name }} ({{ skill.candidate_years }}a / {{ skill.required_years }}a)</span>
              <span v-if="skill.is_mandatory" class="text-[10px] text-amber-500 font-bold">*</span>
            </div>
          </div>

          <div class="mt-3 flex flex-wrap gap-1.5">
            <span
              v-for="factor in cand.factors ?? []"
              :key="factor.key"
              class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-200"
            >
              {{ factor.label }}: {{ factor.score }}%
            </span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
