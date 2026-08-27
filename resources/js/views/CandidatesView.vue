<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import type { Candidate, Skill } from '../types/ats';
import { Users, UserPlus, Search, Mail, Phone, MapPin, DollarSign, X } from 'lucide-vue-next';

const candidates = ref<Candidate[]>([]);
const availableSkills = ref<Skill[]>([]);
const loading = ref(true);
const searchQuery = ref('');

// Modal de Creación
const showCreateModal = ref(false);
const saving = ref(false);
const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  location: '',
  salary_expectation: '',
  summary: '',
  selected_skills: [] as { skill_id: number; years_experience: number; level: string }[],
});

const loadData = async () => {
  loading.value = true;
  try {
    const [candRes, skillsRes] = await Promise.all([
      apiClient.get('/candidates', { params: searchQuery.value ? { search: searchQuery.value } : {} }),
      apiClient.get('/skills')
    ]);
    candidates.value = candRes.data.data.data;
    availableSkills.value = skillsRes.data.data.data;
  } catch (error) {
    console.error('Error loading candidates:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

const handleSearch = () => {
  loadData();
};

const addSkillField = () => {
  if (availableSkills.value.length > 0) {
    form.value.selected_skills.push({
      skill_id: availableSkills.value[0].id,
      years_experience: 1,
      level: 'intermediate',
    });
  }
};

const removeSkillField = (index: number) => {
  form.value.selected_skills.splice(index, 1);
};

const saveCandidate = async () => {
  saving.value = true;
  try {
    const res = await apiClient.post('/candidates', {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      email: form.value.email,
      phone: form.value.phone,
      location: form.value.location,
      salary_expectation: form.value.salary_expectation ? parseFloat(form.value.salary_expectation) : null,
      summary: form.value.summary,
    });

    const newCandidateId = res.data.data.id;

    if (form.value.selected_skills.length > 0) {
      await apiClient.post(`/candidates/${newCandidateId}/skills`, {
        skills: form.value.selected_skills,
      });
    }

    showCreateModal.value = false;
    form.value = {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      location: '',
      salary_expectation: '',
      summary: '',
      selected_skills: [],
    };
    loadData();
  } catch (error: any) {
    alert(error.response?.data?.message || 'Error al guardar el candidato');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
          <Users class="text-sky-400" :size="24" /> Base de Candidatos
        </h1>
        <p class="text-slate-400 text-sm">Directorio global de talento y habilidades técnicas.</p>
      </div>

      <button 
        @click="showCreateModal = true"
        class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 self-start transition shadow-sm"
      >
        <UserPlus :size="16" /> Nuevo Candidato
      </button>
    </div>

    <!-- Buscador -->
    <div class="mb-6 flex items-center gap-3 bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 max-w-md">
      <Search :size="18" class="text-slate-400" />
      <input 
        v-model="searchQuery" 
        @keyup.enter="handleSearch"
        type="text" 
        placeholder="Buscar por nombre o email..." 
        class="bg-transparent border-none outline-none text-sm text-white w-full placeholder-slate-500"
      />
    </div>

    <!-- Listado -->
    <div v-if="loading" class="text-center py-16 text-slate-500">Cargando base de datos...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="cand in candidates" 
        :key="cand.id" 
        class="bg-slate-900 border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition flex flex-col justify-between"
      >
        <div>
          <h2 class="text-base font-bold text-white">{{ cand.first_name }} {{ cand.last_name }}</h2>
          
          <div class="mt-2 space-y-1 text-xs text-slate-400">
            <p class="flex items-center gap-2">
              <Mail :size="13" class="text-slate-500" /> {{ cand.email }}
            </p>
            <p v-if="cand.phone" class="flex items-center gap-2">
              <Phone :size="13" class="text-slate-500" /> {{ cand.phone }}
            </p>
            <p v-if="cand.location" class="flex items-center gap-2">
              <MapPin :size="13" class="text-slate-500" /> {{ cand.location }}
            </p>
          </div>

          <p v-if="cand.summary" class="mt-3 text-xs text-slate-400 line-clamp-2 italic">
            "{{ cand.summary }}"
          </p>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-800/80 flex justify-between items-center text-xs">
          <span v-if="cand.salary_expectation" class="text-emerald-400 font-medium flex items-center">
            <DollarSign :size="13" /> ${{ cand.salary_expectation }}
          </span>
          <span v-else class="text-slate-500">Sin pretensión fijada</span>
        </div>
      </div>
    </div>

    <!-- Modal Nuevo Candidato -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-white">Registrar Candidato</h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="saveCandidate" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-300 font-medium mb-1">Nombre</label>
              <input type="text" v-model="form.first_name" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
            </div>
            <div>
              <label class="block text-slate-300 font-medium mb-1">Apellido</label>
              <input type="text" v-model="form.last_name" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-300 font-medium mb-1">Email</label>
              <input type="email" v-model="form.email" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
            </div>
            <div>
              <label class="block text-slate-300 font-medium mb-1">Teléfono</label>
              <input type="text" v-model="form.phone" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-300 font-medium mb-1">Ubicación</label>
              <input type="text" v-model="form.location" placeholder="Ciudad, País" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
            </div>
            <div>
              <label class="block text-slate-300 font-medium mb-1">Pretensión Salarial (USD)</label>
              <input type="number" v-model="form.salary_expectation" placeholder="2000" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
            </div>
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Resumen Profesional</label>
            <textarea v-model="form.summary" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500"></textarea>
          </div>

          <!-- Skills Dinámicas -->
          <div class="pt-2 border-t border-slate-800">
            <div class="flex justify-between items-center mb-2">
              <span class="font-bold text-slate-300">Habilidades Técnicas</span>
              <button type="button" @click="addSkillField" class="text-sky-400 hover:text-sky-300 text-xs font-semibold">+ Agregar Skill</button>
            </div>

            <div v-for="(sk, idx) in form.selected_skills" :key="idx" class="flex items-center gap-2 mb-2 bg-slate-950 p-2 rounded-lg border border-slate-800">
              <select v-model="sk.skill_id" class="bg-slate-900 border border-slate-700 text-white rounded p-1.5 flex-1">
                <option v-for="s in availableSkills" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <input type="number" v-model="sk.years_experience" min="1" max="20" placeholder="Años" class="w-16 bg-slate-900 border border-slate-700 text-white rounded p-1.5 text-center" />
              <button type="button" @click="removeSkillField(idx)" class="text-rose-400 hover:text-rose-300 px-1">
                <X :size="15" />
              </button>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3">
            <button type="button" @click="showCreateModal = false" class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300">Cancelar</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-500 text-white font-semibold">
              {{ saving ? 'Guardando...' : 'Crear Candidato' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>