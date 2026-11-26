import axios from 'axios';

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedData<T> {
  data: T[];
  links?: PaginationLinks;
  meta?: PaginationMeta;
}

export function unwrapPaginated<T>(response: { data: { data: PaginatedData<T> } }): PaginatedData<T> {
  return response.data.data;
}

export function unwrapItem<T>(response: { data: { data: T } }): T {
  return response.data.data;
}

export function errorMessage(error: unknown, fallback = 'Ocurrió un error inesperado.'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
    const firstValidationError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;

    return data?.message || firstValidationError || fallback;
  }

  return fallback;
}
