import axios from 'axios';
import { useSessionStore } from '../store/useSessionStore';

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
  },
});

apiClient.interceptors.request.use((config) => {
  const token = useSessionStore.getState().token;

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const code = error?.response?.data?.error?.code;

    if (code === 'unauthenticated') {
      useSessionStore.getState().clearSession();
    }

    return Promise.reject(error);
  },
);
