import { apiClient } from './client';
import type { BackendNotification, NotificationsIndexResponse } from '../types/notifications';

export async function fetchNotifications(): Promise<NotificationsIndexResponse> {
  const { data } = await apiClient.get<NotificationsIndexResponse>('/notifications');
  return data;
}

export async function markNotificationRead(id: string): Promise<BackendNotification> {
  const { data } = await apiClient.patch<{ data: BackendNotification }>(`/notifications/${id}/read`);
  return data.data;
}

export async function markAllNotificationsRead(): Promise<void> {
  await apiClient.patch('/notifications/read-all');
}
