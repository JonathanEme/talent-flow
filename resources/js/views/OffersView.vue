<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '../api/client';
import { errorMessage } from '../api/pagination';
import { usePaginatedList } from '../composables/usePaginatedList';
import { notifyError, notifySuccess } from '../composables/useNotifications';
import PaginationControls from '../components/PaginationControls.vue';
import StatePanel from '../components/StatePanel.vue';
import type { Application, Offer } from '../types/ats';
import {
  DollarSign, Plus, CheckCircle, XCircle, Send,
  Calendar, X, Building2
} from 'lucide-vue-next';

const {
  items: offers,
  meta,
  links,
  loading,
  error,
  load,
  goToPage,
} = usePaginatedList<Offer>('/offers');
const { items: applications, load: loadApplications } = usePaginatedList<Application>('/applications');

const showCreateModal = ref(false);
const saving = ref(false);
const formError = ref<string | null>(null);
const pendingOfferId = ref<number | null>(null);
const form = ref({
  application_id: '',
  salary_offered: '',
  currency: 'USD',
  start_date: '',
  expiration_date: '',
  benefits_summary: '',
  terms_and_conditions: '',
});

const showDeclineModal = ref(false);
const selectedOfferToDecline = ref<Offer | null>(null);
const declineReason = ref('');

const currencyFormatter = new Intl.NumberFormat('es');
const formatSalary = (offer: Offer) => `${offer.currency} ${currencyFormatter.format(Number(offer.salary_offered))}`;

onMounted(async () => {
  await Promise.all([load(), loadApplications()]);
});

const createOffer = async () => {
  saving.value = true;
  formError.value = null;

  try {
    await apiClient.post('/offers', {
      application_id: form.value.application_id,
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
    notifySuccess('Oferta emitida correctamente.');
    await load();
  } catch (caught) {
    formError.value = errorMessage(caught, 'Error al emitir la oferta.');
    notifyError(formError.value);
  } finally {
    saving.value = false;
  }
};

const updateStatus = async (offerId: number, status: string, reason: string | null = null) => {
  if (pendingOfferId.value !== null) return;
  pendingOfferId.value = offerId;

  try {
    await apiClient.patch(`/offers/${offerId}/status`, {
      status,
      decline_reason: reason,
    });
    showDeclineModal.value = false;
    declineReason.value = '';
    notifySuccess('Estado de la oferta actualizado.');
    await load();
  } catch (caught) {
    notifyError(errorMessage(caught, 'Error al actualizar el estado de la oferta.'));
  } finally {
    pendingOfferId.value = null;
  }
};

const openDeclineModal = (offer: Offer) => {
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
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
          <DollarSign class="text-emerald-400" :size="24" /> GestiÃƒÂ³n de Ofertas Formales
        </h1>
        <p class="text-slate-400 text-sm">EmisiÃƒÂ³n de propuestas econÃƒÂ³micas, cartas de contrataciÃƒÂ³n y control de estados.</p>
      </div>

      <button 
        @click="showCreateModal = true"
        class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition"
      >
        <Plus :size="16" /> Emitir Nueva Oferta
      </button>
    </div>

    <StatePanel
      v-if="loading || error || offers.length === 0"
      :loading="loading"
      :error="error"
      :empty="offers.length === 0"
      loading-label="Cargando propuestas..."
      empty-label="No hay ofertas formales emitidas todavÃƒÂ­a."
      @retry="load"
    />

    <template v-else>
      <div class="space-y-4">
      <div 
        v-for="offer in offers" 
        :key="offer.id"
        class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm"
      >
        <div>
          <div class="flex items-center gap-2.5">
            <span 
              class="text-xs font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
              :class="{
                'bg-slate-100 text-slate-700': offer.status === 'draft',
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

          <h2 class="text-lg font-bold text-slate-900 mt-1.5">
            {{ offer.application?.candidate?.first_name }} {{ offer.application?.candidate?.last_name }}
          </h2>
          <p class="text-xs text-slate-400">{{ offer.application?.position?.title }}</p>

          <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-slate-400">
            <span class="text-emerald-400 font-bold text-sm">
              {{ formatSalary(offer) }}
            </span>
            <span class="flex items-center gap-1">
              <Calendar :size="13" class="text-slate-500" /> Ingreso: {{ offer.start_date }}
            </span>
          </div>

          <p v-if="offer.decline_reason" class="text-xs text-rose-400 mt-2 bg-rose-950/20 p-2 rounded border border-rose-900/30">
            Motivo de declinaciÃƒÂ³n: {{ offer.decline_reason }}
          </p>
        </div>

        <!-- Acciones del Recruiter -->
        <div class="flex items-center gap-2 self-end md:self-center">
          <button
            v-if="offer.status === 'draft'"
            @click="updateStatus(offer.id, 'sent')"
            :disabled="pendingOfferId !== null"
            class="bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
          >
            <Send :size="13" /> Marcar como Enviada
          </button>

          <button
            v-if="offer.status === 'sent'"
            @click="updateStatus(offer.id, 'accepted')"
            :disabled="pendingOfferId !== null"
            class="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
          >
            <CheckCircle :size="13" /> Candidato AceptÃƒÂ³
          </button>

          <button
            v-if="offer.status === 'sent'"
            @click="openDeclineModal(offer)"
            :disabled="pendingOfferId !== null"
            class="bg-rose-600/20 hover:bg-rose-600 disabled:opacity-50 text-rose-400 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
          >
            <XCircle :size="13" /> Candidato DeclinÃƒÂ³
          </button>
        </div>
      </div>
      </div>

      <PaginationControls :meta="meta" :links="links" @change="goToPage" />
    </template>

    <!-- Modal Emitir Oferta -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white border border-slate-200 rounded-xl max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="offer-modal-title">
        <div class="flex justify-between items-center mb-4">
          <h3 id="offer-modal-title" class="text-lg font-bold text-slate-900">Emitir Oferta Formal</h3>
          <button type="button" aria-label="Cerrar" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="createOffer" class="space-y-4 text-xs">
          <div>
            <label class="block text-slate-700 font-medium mb-1">Candidatura en Proceso</label>
            <select v-model="form.application_id" required class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700 outline-none focus:ring-2 focus:ring-sky-500">
              <option value="" disabled>Selecciona una candidatura</option>
              <option v-for="app in applications" :key="app.id" :value="app.id">
                {{ app.candidate?.first_name }} {{ app.candidate?.last_name }} Ã¢â‚¬â€ {{ app.position?.title }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
              <label class="block text-slate-700 font-medium mb-1">Salario Ofrecido</label>
              <input type="number" v-model="form.salary_offered" required placeholder="2500" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700 outline-none focus:ring-2 focus:ring-sky-500" />
            </div>
            <div>
              <label class="block text-slate-700 font-medium mb-1">Moneda</label>
              <select v-model="form.currency" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700">
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="ARS">ARS</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-700 font-medium mb-1">Fecha Estimada de Ingreso</label>
              <input type="date" v-model="form.start_date" required class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700" />
            </div>
            <div>
              <label class="block text-slate-700 font-medium mb-1">Fecha LÃƒÂ­mite de Respuesta</label>
              <input type="date" v-model="form.expiration_date" class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700" />
            </div>
          </div>

          <div>
            <label class="block text-slate-700 font-medium mb-1">Resumen de Beneficios (Perks)</label>
            <textarea v-model="form.benefits_summary" rows="2" placeholder="Cobertura mÃƒÂ©dica prepaga, bono anual, equipamiento..." class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700"></textarea>
          </div>

          <div>
            <label class="block text-slate-700 font-medium mb-1">TÃƒÂ©rminos y Condiciones</label>
            <textarea v-model="form.terms_and_conditions" rows="2" placeholder="PerÃƒÂ­odo de prueba, clÃƒÂ¡usulas de confidencialidad..." class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-slate-700"></textarea>
          </div>

          <p v-if="formError" class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-xs text-rose-700" role="alert">{{ formError }}</p>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showCreateModal = false" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
              {{ saving ? 'Emitiendo...' : 'Generar Oferta' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Declinar Oferta -->
    <div v-if="showDeclineModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white border border-slate-200 rounded-xl max-w-md w-full p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="decline-modal-title">
        <h3 id="decline-modal-title" class="text-base font-bold text-slate-900 mb-2">Registrar DeclinaciÃƒÂ³n de Oferta</h3>
        <p class="text-xs text-slate-400 mb-4">Ingresa el motivo por el cual el candidato rechazÃƒÂ³ la propuesta (contraoferta, salario bajo, etc.):</p>

        <textarea v-model="declineReason" rows="3" required placeholder="Motivo del rechazo..." class="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-xs text-slate-700 outline-none focus:ring-2 focus:ring-rose-500 mb-4"></textarea>

        <div class="flex justify-end gap-2 text-xs">
          <button type="button" @click="showDeclineModal = false" class="px-3 py-2 rounded bg-slate-100 text-slate-700">Cancelar</button>
          <button
            @click="selectedOfferToDecline && updateStatus(selectedOfferToDecline.id, 'declined', declineReason)"
            :disabled="!declineReason.trim() || pendingOfferId !== null"
            class="px-4 py-2 rounded bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-semibold"
          >
            Confirmar DeclinaciÃƒÂ³n
          </button>
        </div>
      </div>
    </div>
  </div>
</template>