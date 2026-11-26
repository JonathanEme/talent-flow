import { apiClient } from './client';
import { clearToken, setToken } from './token';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  company_id: number | null;
  is_global: boolean;
}

interface AuthResponse {
  data: {
    user: AuthUser;
    token: string;
    token_type: string;
  };
}

export async function login(email: string, password: string): Promise<AuthUser> {
  const { data } = await apiClient.post<AuthResponse>('/auth/login', { email, password });
  setToken(data.data.token);
  return data.data.user;
}

export async function fetchMe(): Promise<AuthUser | null> {
  try {
    const { data } = await apiClient.get<{ data: AuthUser }>('/auth/me');
    return data.data;
  } catch {
    return null;
  }
}

export async function logout(): Promise<void> {
  try {
    await apiClient.post('/auth/logout');
  } finally {
    clearToken();
  }
}
