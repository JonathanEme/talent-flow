<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { useNotifications } from '../composables/useNotifications';

const { notifications, dismissNotification } = useNotifications();
</script>

<template>
  <div class="fixed bottom-5 right-5 z-[100] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2" aria-live="polite">
    <div
      v-for="item in notifications"
      :key="item.id"
      :role="item.type === 'error' ? 'alert' : 'status'"
      class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg backdrop-blur"
      :class="item.type === 'error'
        ? 'border-rose-200 bg-rose-50/95 text-rose-800'
        : item.type === 'success'
          ? 'border-emerald-200 bg-emerald-50/95 text-emerald-800'
          : 'border-sky-200 bg-sky-50/95 text-sky-800'"
    >
      <span class="flex-1 leading-snug">{{ item.message }}</span>
      <button
        type="button"
        class="shrink-0 rounded p-0.5 opacity-60 transition hover:opacity-100"
        aria-label="Cerrar notificación"
        @click="dismissNotification(item.id)"
      >
        <X :size="15" />
      </button>
    </div>
  </div>
</template>
