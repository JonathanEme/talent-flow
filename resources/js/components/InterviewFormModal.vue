<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { apiClient } from '../api/client';
import { errorMessage } from '../api/pagination';
import { notifyError } from '../composables/useNotifications';
import type { Application } from '../types/ats';
import { CalendarPlus, X } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
  open: boolean;
  applicationId?: number | null;
  applications?: Application[];
}>(), {
  applicationId: null,
  applications: () => [],
});

const emit = defineEmits<{ close: []; created: [] }>();

const interviewTypes = [
  { value: 'screening', label: 'Screening RRHH' },
  { value: 'hr', label: 'Entrevista RRHH' },
  { value: 'technical', label: 'Entrevista Técnica' },
  { value: 'client', label: 'Entrevista con Cliente' },
  { value: 'final', label: 'Entrevista Final' },
];

const saving = ref(false);
const formError = ref<string | null>(null);
const needsApplication = computed(() => props.applicationId === null);
const form = ref({
  application_id: null as number | null,
  type: 'technical',
  scheduled_at: '',
  duration_minutes: 45,
  location_or_link: '',
  notes: '',
});

const pad = (value: number) => String(value).padStart(2, '0');

const resetForm = () => {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  tomorrow.setHours(10, 0, 0, 0);

  form.value = {
    application_id: props.applicationId ?? props.applications[0]?.id ?? null,
    type: 'technical',
    scheduled_at: `${tomorrow.getFullYear()}-${pad(tomorrow.getMonth() + 1)}-${pad(tomorrow.getDate())}T${pad(tomorrow.getHours())}:${pad(tomorrow.getMinutes())}`,
    duration_minutes: 45,
    location_or_link: '',
    notes: '',
  };
  formError.value = null;
};

watch(() => props.open, (open) => {
  if (open) resetForm();
}, { immediate: true });

const submit = async () => {
  if (!form.value.application_id) {
    formError.value = 'Selecciona una candidatura para la entrevista.';
    notifyError(formError.value);
    return;
  }

  if (!form.value.scheduled_at || new Date(form.value.scheduled_at) <= new Date()) {
    formError.value = 'La fecha y hora deben ser futuras.';
    notifyError(formError.value);
    return;
  }

  saving.value = true;
  formError.value = null;

  try {
    await apiClient.post('/interviews', {
      application_id: form.value.application_id,
      type: form.value.type,
      scheduled_at: form.value.scheduled_at,
      duration_minutes: Number(form.value.duration_minutes),
      location_or_link: form.value.location_or_link,
      notes: form.value.notes,
    });
    emit('created');
    emit('close');
  } catch (caught) {
    formError.value = errorMessage(caught, 'No se pudo agendar la entrevista.');
    notifyError(formError.value);
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div v-if="open" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="interview-modal-title">
      <div class="flex justify-between items-center mb-4">
        <h3 id="interview-modal-title" class="text-base font-bold text-slate-900 flex items-center gap-2">
          <CalendarPlus :size="18" class="text-indigo-600" /> Agendar Entrevista
        </h3>
        <button type="button" aria-label="Cerrar" @click="emit('close')" class="text-slate-400 hover:text-slate-600">
          <X :size="18" />
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-4 text-xs">
        <div v-if="needsApplication">
          <label class="block text-slate-700 font-semibold mb-1">Candidatura</label>
          <select v-model="form.application_id" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500">
            <option :value="null" disabled>Selecciona una candidatura</option>
            <option v-for="app in applications" :key="app.id" :value="app.id">
              {{ app.candidate?.first_name }} {{ app.candidate?.last_name }} · {{ app.position?.title ?? 'Sin vacante' }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-slate-700 font-semibold mb-1">Tipo de Entrevista</label>
          <select v-model="form.type" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500">
            <option v-for="type in interviewTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
        </div>

        <div>
          <label class="block text-slate-700 font-semibold mb-1">Fecha y Hora</label>
          <input type="datetime-local" v-model="form.scheduled_at" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label class="block text-slate-700 font-semibold mb-1">Duración (minutos)</label>
          <input type="number" v-model.number="form.duration_minutes" min="15" max="240" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label class="block text-slate-700 font-semibold mb-1">Enlace o ubicación (opcional)</label>
          <input type="text" v-model="form.location_or_link" maxlength="255" placeholder="https://meet.google.com/... o Sala 3" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label class="block text-slate-700 font-semibold mb-1">Notas (opcional)</label>
          <textarea v-model="form.notes" rows="2" maxlength="5000" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <p v-if="formError" class="rounded-lg bg-rose-50 p-2.5 text-xs text-rose-700" role="alert">{{ formError }}</p>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="emit('close')" class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">Cancelar</button>
          <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-semibold shadow-sm">
            {{ saving ? 'Guardando...' : 'Confirmar Entrevista' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
