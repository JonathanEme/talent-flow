import { reactive } from 'vue';

export type NotificationType = 'success' | 'error' | 'info';

export interface Notification {
  id: number;
  type: NotificationType;
  message: string;
}

const state = reactive<{ items: Notification[] }>({ items: [] });
let nextId = 1;

export function dismissNotification(id: number): void {
  const index = state.items.findIndex((item) => item.id === id);
  if (index >= 0) {
    state.items.splice(index, 1);
  }
}

export function notify(type: NotificationType, message: string): void {
  const id = nextId++;
  state.items.push({ id, type, message });
  window.setTimeout(() => dismissNotification(id), 5000);
}

export function notifySuccess(message: string): void {
  notify('success', message);
}

export function notifyError(message: string): void {
  notify('error', message);
}

export function useNotifications() {
  return {
    notifications: state.items,
    dismissNotification,
    notify,
    notifySuccess,
    notifyError,
  };
}
