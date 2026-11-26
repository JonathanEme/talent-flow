<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from '../api/client';
import { errorMessage, unwrapItem, unwrapPaginated } from '../api/pagination';
import { notifyError } from '../composables/useNotifications';
import type { Company, Skill } from '../types/ats';
import { Briefcase, ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';

const router = useRouter();
const companies = ref<Company[]>([]);
const availableSkills = ref<Skill[]>([]);
const saving = ref(false);
const formError = ref<string | null>(null);

const form = ref({
  company_id: '',
  title: '',
  description: '',
  seniority: 'SSR',
  modality: 'remote',
  location: '',
  employment_type: 'full-time',
  salary_min: '',
  salary_max: '',
  opened_at: new Date().toISOString().slice(0, 10),
  status: 'open',
  requirements: [] as {
    skill_id: number;
    is_mandatory: boolean;
    min_years_experience: number;
    expected_level: string;
  }[],
});

onMounted(async () => {
  try {
    const [compRes, skillRes] = await Promise.all([
      apiClient.get('/companies'),
      apiClient.get('/skills'),
    ]);
    companies.value = unwrapPaginated<Company>(compRes as { data: { data: { data: Company[] } } }).data;
    availableSkills.value = unwrapPaginated<Skill>(skillRes as { data: { data: { data: Skill[] } } }).data;
  } catch (caught) {
    formError.value = errorMessage(caught, 'No se pudieron cargar empresas y habilidades.');
    notifyError(formError.value);
  }
});

const addRequirement = () => {
  form.value.requirements.push({
    skill_id: 0,
    is_mandatory: true,
    min_years_experience: 2,
    expected_level: 'intermediate',
  });
};

const removeRequirement = (index: number) => {
  form.value.requirements.splice(index, 1);
};

const submitPosition = async () => {
  if (form.value.requirements.some((requirement) => !requirement.skill_id)) {
    formError.value = 'Selecciona una habilidad para cada requisito agregado.';
    notifyError(formError.value);
    return;
  }

  saving.value = true;
  formError.value = null;

  try {
    const response = await apiClient.post('/positions', {
      company_id: form.value.company_id,
      title: form.value.title,
      description: form.value.description,
      seniority: form.value.seniority,
      modality: form.value.modality,
      location: form.value.location || null,
      employment_type: form.value.employment_type,
      salary_min: form.value.salary_min ? parseFloat(form.value.salary_min) : null,
      salary_max: form.value.salary_max ? parseFloat(form.value.salary_max) : null,
      opened_at: form.value.opened_at,
      status: form.value.status,
    });

    const newPosition = unwrapItem<{ id: number }>(response as { data: { data: { id: number } } });

    if (form.value.requirements.length > 0) {
      await apiClient.post(`/positions/${newPosition.id}/requirements`, {
        skills: form.value.requirements,
      });
    }

    await router.push(`/positions/${newPosition.id}`);
  } catch (caught) {
    formError.value = errorMessage(caught, 'Error al crear la posición.');
    notifyError(formError.value);
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div class="max-w-3xl mx-auto">
    <router-link to="/positions" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 mb-6">
      <ArrowLeft :size="16" /> Volver a vacantes
    </router-link>

    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
      <h1 class="text-xl font-bold text-slate-900 mb-1 flex items-center gap-2">
        <Briefcase class="text-sky-600" :size="20" /> Nueva Vacante
      </h1>
      <p class="text-slate-500 text-xs mb-6">Configura la búsqueda y los requisitos obligatorios para el matching automático.</p>

      <form @submit.prevent="submitPosition" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
            <select v-model="form.company_id" required class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700 outline-none focus:ring-2 focus:ring-sky-500">
              <option value="" disabled>Selecciona una empresa</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
            <input type="text" v-model="form.title" required placeholder="Ej. Tech Lead Node / React" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700 outline-none focus:ring-2 focus:ring-sky-500" />
          </div>
        </div>

        <div>
          <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
          <textarea v-model="form.description" required rows="3" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700 outline-none focus:ring-2 focus:ring-sky-500"></textarea>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
            <select v-model="form.seniority" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700">
              <option value="JR">Junior</option>
              <option value="SSR">Semi Senior</option>
              <option value="SR">Senior</option>
              <option value="LEAD">Tech Lead</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
            <select v-model="form.modality" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700">
              <option value="remote">Remoto</option>
              <option value="hybrid">Híbrido</option>
              <option value="on_site">Presencial</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
            <input type="text" v-model="form.location" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
            <input type="number" v-model="form.salary_min" placeholder="1500" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700" />
          </div>
          <div>
            <label class="block text-slate-700 font-medium mb-1">Título de la Posición</label>
            <input type="number" v-model="form.salary_max" placeholder="2500" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700" />
          </div>
        </div>

        <!-- Matriz de Requisitos para el Matching -->
        <div class="pt-4 border-t border-slate-200">
          <div class="flex justify-between items-center mb-3">
            <div>
              <h3 class="font-bold text-slate-200">Requisitos Técnicos (Matching Engine)</h3>
              <p class="text-[11px] text-slate-500">Define los dealbreakers y los deseables.</p>
            </div>
            <button type="button" @click="addRequirement" class="bg-slate-800 hover:bg-slate-700 text-sky-400 px-3 py-1.5 rounded text-xs font-semibold flex items-center gap-1">
              <Plus :size="14" /> Agregar Requisito
            </button>
          </div>

          <div class="space-y-2">
            <div 
              v-for="(req, idx) in form.requirements" 
              :key="idx"
              class="flex items-center gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200"
            >
              <select v-model="req.skill_id" class="bg-white border border-slate-300 text-slate-700 rounded p-1.5 flex-1" :aria-label="`Habilidad ${idx + 1}`">
                <option :value="0" disabled>Selecciona habilidad</option>
                <option v-for="s in availableSkills" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>

              <div class="flex items-center gap-1">
                <span class="text-slate-400">Min:</span>
                <input type="number" v-model="req.min_years_experience" min="1" max="20" class="w-14 bg-white border border-slate-300 text-slate-700 rounded p-1.5 text-center" />
                <span class="text-slate-500">años</span>
              </div>

              <label class="flex items-center gap-1.5 cursor-pointer px-2">
                <input type="checkbox" v-model="req.is_mandatory" class="accent-sky-500 rounded" />
                <span class="text-slate-600 font-medium">Obligatorio</span>
              </label>

              <button type="button" @click="removeRequirement(idx)" class="text-rose-400 hover:text-rose-300 p-1">
                <Trash2 :size="15" />
              </button>
            </div>
          </div>
        </div>

        <p v-if="formError" class="rounded-lg bg-rose-500/10 p-2.5 text-xs text-rose-300" role="alert">{{ formError }}</p>

        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="router.push('/positions')" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold">Cancelar</button>
          <button type="submit" :disabled="saving" class="px-5 py-2 rounded-lg bg-sky-600 hover:bg-sky-500 text-white font-semibold">
            {{ saving ? 'Publicando...' : 'Crear y Calcular Matching' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>