<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import { errorMessage } from '../api/pagination';
import { usePaginatedList } from '../composables/usePaginatedList';
import { notifyError, notifySuccess } from '../composables/useNotifications';
import PaginationControls from '../components/PaginationControls.vue';
import StatePanel from '../components/StatePanel.vue';
import type { Company } from '../types/ats';
import { Building2, Plus, Mail, Phone, Globe, X } from 'lucide-vue-next';

const {
  items: companies,
  meta,
  links,
  loading,
  error,
  load,
  goToPage,
} = usePaginatedList<Company>('/companies');

const showModal = ref(false);
const saving = ref(false);
const formError = ref<string | null>(null);
const form = ref({
  name: '',
  tax_id: '',
  email: '',
  phone: '',
  website: '',
  description: '',
});

onMounted(load);

const saveCompany = async () => {
  saving.value = true;
  formError.value = null;

  try {
    await apiClient.post('/companies', form.value);
    showModal.value = false;
    form.value = {
      name: '',
      tax_id: '',
      email: '',
      phone: '',
      website: '',
      description: '',
    };
    notifySuccess('Empresa registrada correctamente.');
    await load();
  } catch (caught) {
    formError.value = errorMessage(caught, 'Error al guardar la empresa.');
    notifyError(formError.value);
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
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
          <Building2 class="text-indigo-600" :size="24" /> Empresas / Clientes
        </h1>
        <p class="text-slate-500 text-sm">Gestiona cuentas corporativas y clientes con bÃºsquedas activas.</p>
      </div>

      <button 
        @click="showModal = true"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition shadow-sm"
      >
        <Plus :size="16" /> Nueva Empresa
      </button>
    </div>

    <StatePanel
      v-if="loading || error || companies.length === 0"
      :loading="loading"
      :error="error"
      :empty="companies.length === 0"
      loading-label="Cargando empresas..."
      empty-label="No hay empresas registradas aÃºn. Haz clic en â€œNueva Empresaâ€ para agregar la primera."
      @retry="load"
    />

    <template v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="c in companies" 
        :key="c.id"
        class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm hover:shadow-md hover:border-slate-300 transition"
      >
        <div>
          <h2 class="text-base font-bold text-slate-900">{{ c.name }}</h2>
          <a 
            v-if="c.website" 
            :href="c.website" 
            target="_blank" 
            class="text-xs text-indigo-600 hover:underline mt-0.5 inline-flex items-center gap-1 font-medium"
          >
            <Globe :size="12" /> {{ c.website }}
          </a>

          <div class="mt-3 space-y-1.5 text-xs text-slate-500">
            <p v-if="c.email" class="flex items-center gap-2">
              <Mail :size="13" class="text-slate-400" /> {{ c.email }}
            </p>
            <p v-if="c.phone" class="flex items-center gap-2">
              <Phone :size="13" class="text-slate-400" /> {{ c.phone }}
            </p>
          </div>
        </div>
      </div>
      </div>

      <PaginationControls :meta="meta" :links="links" @change="goToPage" />
    </template>

    <!-- Modal Nueva Empresa -->
    <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="company-modal-title">
        <div class="flex justify-between items-center mb-4">
          <h3 id="company-modal-title" class="text-base font-bold text-slate-900">Registrar Empresa</h3>
          <button type="button" aria-label="Cerrar" @click="showModal = false" class="text-slate-400 hover:text-slate-600">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="saveCompany" class="space-y-4 text-xs">
          <div>
            <label class="block text-slate-700 font-semibold mb-1">RazÃ³n Social / Nombre</label>
            <input type="text" v-model="form.name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-700 font-semibold mb-1">IdentificaciÃ³n Fiscal</label>
              <input type="text" v-model="form.tax_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-slate-700 font-semibold mb-1">Sitio Web</label>
              <input type="url" v-model="form.website" placeholder="https://..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-700 font-semibold mb-1">Email de Contacto</label>
              <input type="email" v-model="form.email" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-slate-700 font-semibold mb-1">TelÃ©fono</label>
              <input type="text" v-model="form.phone" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>

          <div>
            <label class="block text-slate-700 font-semibold mb-1">DescripciÃ³n</label>
            <textarea v-model="form.description" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
          </div>

          <p v-if="formError" class="rounded-lg bg-rose-50 p-2.5 text-xs text-rose-700" role="alert">{{ formError }}</p>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showModal = false" class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">Cancelar</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm">
              {{ saving ? 'Guardando...' : 'Crear Empresa' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>