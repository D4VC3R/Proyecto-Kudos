import axiosClient from '../../core/axiosClient.js';
import { useBaseMutation } from '../common/useBaseMutation.js';

// Reporta un comentario, una lista o un perfil. El 409 (duplicado) y el 429 salen en el toast con el mensaje del backend.
export const useCreateReport = () =>
  useBaseMutation({
    mutationFn: ({ type, id, reason, details }) =>
      axiosClient.post('/reports', {
        reportable_type: type,
        reportable_id: id,
        reason,
        details: details || null,
      }),
    successMessage: (res) => res?.message ?? 'Gracias. Revisaremos tu reporte.',
  });
