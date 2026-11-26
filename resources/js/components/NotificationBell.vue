<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Bell, CheckCheck } from 'lucide-vue-next';
import { usePersistentNotifications } from '../composables/usePersistentNotifications';
import type { BackendNotification } from '../types/notifications';

const router = useRouter();
const open = ref(false);
const { items, unreadCount, loading, refresh, markRead, markAllRead, startPolling, stopPolling } = usePersistentNotifications();

const toggle = async (): Promise<void> => {
  open.value = !open.value;
  if (open.value) {
    await refresh();
  }
};

const onDocumentClick = (event: MouseEvent): void => {
  const target = event.target as HTMLElement | null;
  if (!target?.closest('[data-notification-bell]')) {
    open.value = false;
  }
};

const openNotification = async (notification: BackendNotification): Promise<void> => {
  if (!notification.read_at) {
    await markRead(notification.id);
  }

  open.value = false;

  if (notification.url) {
    await router.push(notification.url);
  }
};

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  startPolling();
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  stopPolling();
});
</script>

<template>
  <div class="relative" data-notification-bell>
    <button
      type="button"
      class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
      title="Notificaciones"
      @click="toggle"
    >
      <Bell :size="17" />
      <span
        v-if="unreadCount > 0"
        class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <div
      v-if="open"
      class="absolute right-0 top-full z-50 mt-2 w-96 rounded-xl border border-slate-200 bg-white shadow-xl"
    >
      <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <div>
          <p class="text-sm font-semibold text-slate-900">Notificaciones</p>
          <p class="text-xs text-slate-500">{{ unreadCount }} sin leer</p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700"
          @click="markAllRead"
        >
          <CheckCheck :size="14" /> Marcar todas
        </button>
      </div>

      <div class="max-h-96 overflow-y-auto">
        <p v-if="loading && items.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">Cargando...</p>
        <p v-else-if="items.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">No hay notificaciones.</p>

        <button
          v-for="notification in items"
          :key="notification.id"
          type="button"
          class="block w-full border-b border-slate-50 px-4 py-3 text-left transition hover:bg-slate-50"
          :class="notification.read_at ? 'bg-white' : 'bg-indigo-50/30'"
          @click="openNotification(notification)"
        >
          <div class="flex items-start gap-2">
            <span class="mt-1 h-2 w-2 rounded-full" :class="notification.read_at ? 'bg-transparent' : 'bg-indigo-500'" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-slate-900">{{ notification.title }}</p>
              <p class="mt-0.5 text-xs text-slate-600">{{ notification.message }}</p>
              <p class="mt-1 text-[11px] text-slate-400">{{ notification.created_at ? new Date(notification.created_at).toLocaleString() : '' }}</p>
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
