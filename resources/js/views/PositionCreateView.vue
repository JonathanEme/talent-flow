<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from '../api/client';
import type { Company, Skill } from '../types/ats';
import { Briefcase, ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';

const router = useRouter();
const companies = ref<Company[]>([]);
const availableSkills = ref<Skill[]>([]);
const saving = ref(false);

const form = ref({
  company_id: '',
  title: '',
  description: '',
  seniority: 'SSR',
  modality: 'remote',
  location: 'Argentina',
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
      apiClient.get('/skills')
    ]);
    companies.value = compRes.data.data.data;
    availableSkills.value = skillRes.data.data.data;

    if (companies.value.length > 0) {
      form.value.company_id = String(companies.value[0].id);
    }
  } catch (error) {
    console.error('Error fetching requirements:', error);
  }
});

const addRequirement = () => {
  if (availableSkills.value.length > 0) {
    form.value.requirements.push({
      skill_id: availableSkills.value[0].id,
      is_mandatory: true,
      min_years_experience: 2,
      expected_level: 'intermediate',
    });
  }
};

const removeRequirement = (index: number) => {
  form.value.requirements.splice(index, 1);
};

const submitPosition = async () => {
  saving.value = true;
  try {
    const res = await apiClient.post('/positions', {
      company_id: form.value.company_id,
      title: form.value.title,
      description: form.value.description,
      seniority: form.value.seniority,
      modality: form.value.modality,
      location: form.value.location,
      employment_type: form.value.employment_type,
      salary_min: form.value.salary_min ? parseFloat(form.value.salary_min) : null,
      salary_max: form.value.salary_max ? parseFloat(form.value.salary_max) : null,
      opened_at: form.value.opened_at,
      status: form.value.status,
    });

    const newPositionId = res.data.data.id;

    if (form.value.requirements.length > 0) {
      await apiClient.post(`/positions/${newPositionId}/requirements`, {
        skills: form.value.requirements,
      });
    }

    router.push(`/positions/${newPositionId}`);
  } catch (error: any) {
    alert(error.response?.data?.message || 'Error al crear la posición.');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div class="max-w-3xl mx-auto">
    <router-link to="/positions" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-white mb-6">
      <ArrowLeft :size="16" /> Volver a vacantes
    </router-link>

    <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm">
      <h1 class="text-xl font-bold text-white mb-1 flex items-center gap-2">
        <Briefcase class="text-sky-400" :size="20" /> Nueva Vacante
      </h1>
      <p class="text-slate-400 text-xs mb-6">Configura la búsqueda y los requisitos obligatorios para el matching automático.</p>

      <form @submit.prevent="submitPosition" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-slate-300 font-medium mb-1">Empresa / Cliente</label>
            <select v-model="form.company_id" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500">
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-300 font-medium mb-1">Título de la Posición</label>
            <input type="text" v-model="form.title" required placeholder="Ej. Tech Lead Node / React" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
          </div>
        </div>

        <div>
          <label class="block text-slate-300 font-medium mb-1">Descripción del Puesto</label>
          <textarea v-model="form.description" required rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500"></textarea>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-slate-300 font-medium mb-1">Seniority</label>
            <select v-model="form.seniority" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
              <option value="JR">Junior</option>
              <option value="SSR">Semi Senior</option>
              <option value="SR">Senior</option>
              <option value="LEAD">Tech Lead</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-300 font-medium mb-1">Modalidad</label>
            <select v-model="form.modality" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
              <option value="remote">Remoto</option>
              <option value="hybrid">Híbrido</option>
              <option value="on_site">Presencial</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-300 font-medium mb-1">Ubicación</label>
            <input type="text" v-model="form.location" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-300 font-medium mb-1">Salario Mínimo (USD)</label>
            <input type="number" v-model="form.salary_min" placeholder="1500" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white" />
          </div>
          <div>
            <label class="block text-slate-300 font-medium mb-1">Salario Máximo (USD)</label>
            <input type="number" v-model="form.salary_max" placeholder="2500" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white" />
          </div>
        </div>

        <!-- Matriz de Requisitos para el Matching -->
        <div class="pt-4 border-t border-slate-800">
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
              class="flex items-center gap-2 bg-slate-950 p-2.5 rounded-lg border border-slate-800"
            >
              <select v-model="req.skill_id" class="bg-slate-900 border border-slate-700 text-white rounded p-1.5 flex-1">
                <option v-for="s in availableSkills" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>

              <div class="flex items-center gap-1">
                <span class="text-slate-400">Min:</span>
                <input type="number" v-model="req.min_years_experience" min="1" max="20" class="w-14 bg-slate-900 border border-slate-700 text-white rounded p-1.5 text-center" />
                <span class="text-slate-500">años</span>
              </div>

              <label class="flex items-center gap-1.5 cursor-pointer px-2">
                <input type="checkbox" v-model="req.is_mandatory" class="accent-sky-500 rounded" />
                <span class="text-slate-300 font-medium">Obligatorio</span>
              </label>

              <button type="button" @click="removeRequirement(idx)" class="text-rose-400 hover:text-rose-300 p-1">
                <Trash2 :size="15" />
              </button>
            </div>
          </div>
        </div>

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