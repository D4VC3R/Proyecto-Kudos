import axios from 'axios';
import {useSessionStore} from '../store/useSessionStore.js';
import toast from 'react-hot-toast';

/**
 * Instancia de Axios configurada para interactuar con la API del backend.
 * Incluye interceptores para manejar la autenticación y los errores de forma centralizada.
 *
 * @type {axios.AxiosInstance}
 */
const axiosClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

/** Interceptor de solicitud para agregar el token de autenticación a cada petición. */
axiosClient.interceptors.request.use((config) => {
  const state = useSessionStore.getState();
  const token = state.token;

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

/**
 * Interceptor de respuesta para manejar errores de forma centralizada.
 * Si la respuesta es correcta, devuelve solo los datos.
 * Si hay un error, extrae el mensaje del backend o usa un mensaje genérico.
 * En caso de error 401, limpia la sesión y muestra una notificación si el usuario estaba autenticado.
 */
axiosClient.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error) => {
    const backendMessage = error.response?.data?.error?.message;
    const validationError = error.response?.data?.message;
    const finalMessage = backendMessage || validationError || error.message || 'Error inesperado del servidor';

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