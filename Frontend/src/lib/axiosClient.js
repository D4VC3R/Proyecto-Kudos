import axios from 'axios';
import { useSessionStore } from '../store/useSessionStore';

const axiosClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

axiosClient.interceptors.request.use((config) => {
  // Obtenemos el estado actual directamente de Zustand sin hooks
  const state = useSessionStore.getState();
  const token = state.token;

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

axiosClient.interceptors.response.use(
  (response) => {
    // Los controladores en Laravel devuelven una estructura estandarizada
    // Retornamos directamente el contenido para no desestructurar {data: {data: ...}} constantemente
    return response.data;
  },
  (error) => {
    const backendMessage = error.response?.data?.error?.message;
    const validationError = error.response?.data?.message;
    const finalMessage = backendMessage || validationError || error.message || 'Error inesperado del servidor';

    // Si la API rechaza el token (401), limpiamos la sesión automáticamente
    if (error.response?.status === 401) {
      useSessionStore.getState().clearSession();
    }

    return Promise.reject(new Error(finalMessage));
  }
);

export default axiosClient;