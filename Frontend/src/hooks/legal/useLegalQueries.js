import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';

// Datos públicos de las páginas legales (F3-07): el correo de contacto sale de LEGAL_CONTACT_EMAIL en el backend.
export const LEGAL_KEYS = {
  info: ['legal', 'info'],
};

export const useLegalInfo = () => useQuery({
  queryKey: LEGAL_KEYS.info,
  queryFn: () => axiosClient.get('/legal'),
  select: (response) => response.data,
  staleTime: Infinity,
  retry: false,
});
