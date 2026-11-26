import { onScopeDispose, ref } from 'vue';
import { fetchNotifications, markAllNotificationsRead, markNotificationRead } from '../api/notifications';
import type { BackendNotification } from '../types/notifications';

const items = ref<BackendNotification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);
let timer: number | undefined;
let inFlight = false;

export function usePersistentNotifications() {
  const refresh = async (): Promise<void> => {
    if (inFlight) {
      return;
    }

    inFlight = true;
    loading.value = true;

    try {
      const response = await fetchNotifications();
      items.value = response.data.data;
      unreadCount.value = response.unread_count;
    } finally {
      loading.value = false;
      inFlight = false;
    }
  };

  const markRead = async (id: string): Promise<void> => {
    const updated = await markNotificationRead(id);
    items.value = items.value.map((item) => (item.id === id ? updated : item));
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  };

  const markAllRead = async (): Promise<void> => {
    await markAllNotificationsRead();
    const now = new Date().toISOString();
    items.value = items.value.map((item) => ({ ...item, read_at: item.read_at ?? now }));
    unreadCount.value = 0;
  };

  const startPolling = (intervalMs = 30000): void => {
    stopPolling();
    void refresh();
    timer = window.setInterval(() => {
      void refresh();
    }, intervalMs);
  };

  const stopPolling = (): void => {
    if (timer) {
      window.clearInterval(timer);
      timer = undefined;
    }
  };

  onScopeDispose(stopPolling);

  return {
    items,
    unreadCount,
    loading,
    refresh,
    markRead,
    markAllRead,
    startPolling,
    stopPolling,
  };
}
