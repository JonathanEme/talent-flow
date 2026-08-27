<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import { 
  DollarSign, Plus, CheckCircle, XCircle, Send, 
  FileText, Calendar, X, Building2 
} from 'lucide-vue-next';

const offers = ref<any[]>([]);
const applications = ref<any[]>([]);
const loading = ref(true);

// Modal Crear Oferta
const showCreateModal = ref(false);
const saving = ref(false);
const form = ref({
  application_id: '',
  salary_offered: '',
  currency: 'USD',
  start_date: '',
  expiration_date: '',
  benefits_summary: '',
  terms_and_conditions: '',
});

// Modal Declinar Oferta
const showDeclineModal = ref(false);
const selectedOfferToDecline = ref<any>(null);
const declineReason = ref('');

const loadData = async () => {
  loading.value = true;
  try {
    const [offersRes, appsRes] = await Promise.all([
      apiClient.get('/offers'),
      apiClient.get('/applications')
    ]);
    offers.value = offersRes.data.data.data;
    applications.value = appsRes.data.data.data;

    if (applications.value.length > 0) {
      form.value.application_id = String(applications.value[0].id);
    }
  } catch (error) {
    console.error('Error loading offers:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

const createOffer = async () => {
  saving.value = true;
  try {
    await apiClient.post('/offers', {
      application_id: form.value.application_id,
      created_by: 1,
      salary_offered: parseFloat(form.value.salary_offered),
      currency: form.value.currency,
      start_date: form.value.start_date,
      expiration_date: form.value.expiration_date || null,
      benefits_summary: form.value.benefits_summary,
      terms_and_conditions: form.value.terms_and_conditions,
    });
    showCreateModal.value = false;
    form.value = {
      application_id: '',
      salary_offered: '',
      currency: 'USD',
      start_date: '',
      expiration_date: '',
      benefits_summary: '',
      terms_and_conditions: '',
    };
    loadData();
  } catch (error: any) {
    alert(error.response?.data?.message || 'Error al emitir la oferta.');
  } finally {
    saving.value = false;
  }
};

const updateStatus = async (offerId: number, status: string, reason: string | null = null) => {
  try {
    await apiClient.patch(`/offers/${offerId}/status`, {
      status,
      decline_reason: reason,
      user_id: 1,
    });
    showDeclineModal.value = false;
    declineReason.value = '';
    loadData();
  } catch (error: any) {
    alert(error.response?.data?.message || 'Error al actualizar el estado de la oferta.');
  }
};

const openDeclineModal = (offer: any) => {
  selectedOfferToDecline.value = offer;
  declineReason.value = '';
  showDeclineModal.value = true;
};
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
          <DollarSign class="text-emerald-400" :size="24" /> Gestión de Ofertas Formales
        </h1>
        <p class="text-slate-400 text-sm">Emisión de propuestas económicas, cartas de contratación y control de estados.</p>
      </div>

      <button 
        @click="showCreateModal = true"
        class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition"
      >
        <Plus :size="16" /> Emitir Nueva Oferta
      </button>
    </div>

    <!-- Lista de Ofertas -->
    <div v-if="loading" class="text-center py-16 text-slate-500">Cargando propuestas...</div>

    <div v-else-if="offers.length === 0" class="bg-slate-900 border border-slate-800 rounded-xl p-8 text-center text-slate-400 text-sm">
      No hay ofertas formales emitidas todavía.
    </div>

    <div v-else class="space-y-4">
      <div 
        v-for="offer in offers" 
        :key="offer.id"
        class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4"
      >
        <div>
          <div class="flex items-center gap-2.5">
            <span 
              class="text-xs font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
              :class="{
                'bg-slate-800 text-slate-300': offer.status === 'draft',
                'bg-sky-500/10 text-sky-400 border border-sky-500/20': offer.status === 'sent',
                'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': offer.status === 'accepted',
                'bg-rose-500/10 text-rose-400 border border-rose-500/20': offer.status === 'declined',
              }"
            >
              {{ offer.status }}
            </span>
            <span class="text-xs text-slate-400 flex items-center gap-1">
              <Building2 :size="12" /> {{ offer.application?.position?.company?.name }}
            </span>
          </div>

          <h2 class="text-lg font-bold text-white mt-1.5">
            {{ offer.application?.candidate?.first_name }} {{ offer.application?.candidate?.last_name }}
          </h2>
          <p class="text-xs text-slate-400">{{ offer.application?.position?.title }}</p>

          <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-slate-400">
            <span class="text-emerald-400 font-bold text-sm">
              {{ offer.currency }} {{ Number(offer.salary_offered).toLocaleString() }}
            </span>
            <span class="flex items-center gap-1">
              <Calendar :size="13" class="text-slate-500" /> Ingreso: {{ offer.start_date }}
            </span>
          </div>

          <p v-if="offer.decline_reason" class="text-xs text-rose-400 mt-2 bg-rose-950/20 p-2 rounded border border-rose-900/30">
            Motivo de declinación: {{ offer.decline_reason }}
          </p>
        </div>

        <!-- Acciones del Recruiter -->
        <div class="flex items-center gap-2 self-end md:self-center">
          <button 
            v-if="offer.status === 'draft'"
            @click="updateStatus(offer.id, 'sent')"
            class="bg-sky-600 hover:bg-sky-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
          >
            <Send :size="13" /> Marcar como Enviada
          </button>

          <button 
            v-if="offer.status === 'sent'"
            @click="updateStatus(offer.id, 'accepted')"
            class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
          >
            <CheckCircle :size="13" /> Candidato Aceptó
          </button>

          <button 
            v-if="offer.status === 'sent'"
            @click="openDeclineModal(offer)"
            class="bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
          >
            <XCircle :size="13" /> Candidato Declinó
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Emitir Oferta -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-white">Emitir Oferta Formal</h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="createOffer" class="space-y-4 text-xs">
          <div>
            <label class="block text-slate-300 font-medium mb-1">Candidatura en Proceso</label>
            <select v-model="form.application_id" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500">
              <option v-for="app in applications" :key="app.id" :value="app.id">
                {{ app.candidate?.first_name }} {{ app.candidate?.last_name }} — {{ app.position?.title }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
              <label class="block text-slate-300 font-medium mb-1">Salario Ofrecido</label>
              <input type="number" v-model="form.salary_offered" required placeholder="2500" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white outline-none focus:border-sky-500" />
            </div>
            <div>
              <label class="block text-slate-300 font-medium mb-1">Moneda</label>
              <select v-model="form.currency" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="ARS">ARS</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-300 font-medium mb-1">Fecha Estimada de Ingreso</label>
              <input type="date" v-model="form.start_date" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white" />
            </div>
            <div>
              <label class="block text-slate-300 font-medium mb-1">Fecha Límite de Respuesta</label>
              <input type="date" v-model="form.expiration_date" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white" />
            </div>
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Resumen de Beneficios (Perks)</label>
            <textarea v-model="form.benefits_summary" rows="2" placeholder="Cobertura médica prepaga, bono anual, equipamiento..." class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white"></textarea>
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Términos y Condiciones</label>
            <textarea v-model="form.terms_and_conditions" rows="2" placeholder="Período de prueba, cláusulas de confidencialidad..." class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showCreateModal = false" class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300">Cancelar</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
              {{ saving ? 'Emitiendo...' : 'Generar Oferta' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Declinar Oferta -->
    <div v-if="showDeclineModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-base font-bold text-white mb-2">Registrar Declinación de Oferta</h3>
        <p class="text-xs text-slate-400 mb-4">Ingresa el motivo por el cual el candidato rechazó la propuesta (contraoferta, salario bajo, etc.):</p>

        <textarea v-model="declineReason" rows="3" required placeholder="Motivo del rechazo..." class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white outline-none focus:border-rose-500 mb-4"></textarea>

        <div class="flex justify-end gap-2 text-xs">
          <button @click="showDeclineModal = false" class="px-3 py-2 rounded bg-slate-800 text-slate-300">Cancelar</button>
          <button 
            @click="updateStatus(selectedOfferToDecline.id, 'declined', declineReason)" 
            :disabled="!declineReason.trim()"
            class="px-4 py-2 rounded bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-semibold"
          >
            Confirmar Declinación
          </button>
        </div>
      </div>
    </div>
  </div>
</template>