export type NotificationCategory =
  | 'application.created'
  | 'application.stage_changed'
  | 'interview.scheduled'
  | 'interview.status_changed'
  | 'interview.feedback_submitted'
  | 'offer.created'
  | 'offer.status_changed';

export interface BackendNotification {
  id: string;
  category: NotificationCategory | string | null;
  title: string;
  message: string;
  url: string | null;
  read_at: string | null;
  created_at: string | null;
  data: Record<string, unknown>;
}

export interface NotificationsIndexResponse {
  data: {
    data: BackendNotification[];
    links: {
      first: string | null;
      last: string | null;
      prev: string | null;
      next: string | null;
    };
    meta: {
      current_page: number;
      from: number | null;
      last_page: number;
      per_page: number;
      to: number | null;
      total: number;
    };
  };
  unread_count: number;
}
