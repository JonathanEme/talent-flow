<script setup lang="ts">
withDefaults(defineProps<{
  loading: boolean;
  error?: string | null;
  empty?: boolean;
  loadingLabel?: string;
  emptyLabel?: string;
  retryLabel?: string;
}>(), {
  error: null,
  empty: false,
  loadingLabel: 'Cargando información...',
  emptyLabel: 'No hay información para mostrar.',
  retryLabel: 'Reintentar',
});

const emit = defineEmits<{ retry: [] }>();
</script>

<template>
  <div v-if="loading" class="py-16 text-center text-sm text-slate-400" role="status" aria-live="polite">
    {{ loadingLabel }}
  </div>

  <div v-else-if="error" class="mx-auto max-w-lg rounded-2xl border border-rose-200 bg-rose-50 p-8 text-center" role="alert">
    <p class="text-sm font-medium text-rose-700">{{ error }}</p>
    <button
      type="button"
      class="mt-4 rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-rose-500"
      @click="emit('retry')"
    >
      {{ retryLabel }}
    </button>
  </div>

  <div v-else-if="empty" class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500">
    {{ emptyLabel }}
  </div>
</template>
