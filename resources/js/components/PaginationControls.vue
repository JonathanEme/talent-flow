<script setup lang="ts">
import type { PaginationLinks, PaginationMeta } from '../api/pagination';

defineProps<{
  meta: PaginationMeta | null;
  links?: PaginationLinks | null;
}>();

const emit = defineEmits<{ change: [page: number] }>();
</script>

<template>
  <nav
    v-if="meta && meta.last_page > 1"
    class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500"
    aria-label="Paginación de resultados"
  >
    <span>
      Página {{ meta.current_page }} de {{ meta.last_page }} · {{ meta.total }} registros
    </span>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold transition enabled:hover:bg-slate-50 disabled:opacity-40"
        :disabled="!links?.prev"
        @click="emit('change', meta.current_page - 1)"
      >
        Anterior
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold transition enabled:hover:bg-slate-50 disabled:opacity-40"
        :disabled="!links?.next"
        @click="emit('change', meta.current_page + 1)"
      >
        Siguiente
      </button>
    </div>
  </nav>
</template>
