import axios from 'axios';
import { useSessionStore } from '../store/useSessionStore';
import toast from 'react-hot-toast'; // AÑADIMOS EL TOAST

const axiosClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

axiosClient.interceptors.request.use((config) => {
  const state = useSessionStore.getState();
  const token = state.token;

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

axiosClient.interceptors.response.use(
    (response) => {
      return response.data;
    },
    (error) => {
      const backendMessage = error.response?.data?.error?.message;
      const validationError = error.response?.data?.message;
      const finalMessage = backendMessage || validationError || error.message || 'Error inesperado del servidor';

      // Token caducado, inválido o revocado
      if (error.response?.status === 401) {
        const wasAuthenticated = !!useSessionStore.getState().token;

        useSessionStore.getState().clearSession();

        if (wasAuthenticated) {
          toast.error('Tu sesión ha expirado. Por favor, vuelve a iniciar sesión.');
        }
      }

      return Promise.reject(new Error(finalMessage));
    }
);

export default axiosClient;