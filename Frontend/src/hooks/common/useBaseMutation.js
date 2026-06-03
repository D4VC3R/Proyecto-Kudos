import { useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';

/**
 * Hook personalizado para manejar mutaciones con React Query, centralizando la lógica de éxito, error e invalidación de caché.
 *
 * @param mutationFn - Función que realiza la mutación (ej. llamada a API).
 * @param invalidateKeys - Array de claves de consultas a invalidar tras el éxito de la mutación.
 * @param successMessage - Mensaje a mostrar en caso de éxito.
 * @param errorMessage - Mensaje a mostrar en caso de error (opcional).
 * @param onSuccessExtra - Función adicional a ejecutar tras el éxito (opcional).
 * @param onErrorExtra - Función adicional a ejecutar tras el error (opcional).
 * @param onSettledExtra - Función adicional a ejecutar siempre al finalizar la mutación, sin importar el resultado (opcional).
 *
 * @returns Un objeto con las propiedades y métodos proporcionados por `useMutation`.
 */
export const useBaseMutation = ({
                                  mutationFn,
                                  invalidateKeys = [],
                                  successMessage,
                                  errorMessage = 'Ha ocurrido un error inesperado',
                                  onSuccessExtra,
                                  onErrorExtra,
                                  onSettledExtra, // Para acciones que deben ocurrir sí o sí
                                }) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn,
    onSuccess: (data, variables, context) => {
      if (invalidateKeys.length > 0) {
        invalidateKeys.forEach((key) => {
          queryClient.invalidateQueries({ queryKey: key });
        });
      }
      if (successMessage) toast.success(successMessage);
      if (onSuccessExtra) onSuccessExtra(data, variables, context);
    },
    onError: (error, variables, context) => {
      const message = error?.response?.data?.message || error.message || errorMessage;
      toast.error(message);
      if (onErrorExtra) onErrorExtra(error, variables, context);
    },

    onSettled: (data, error, variables, context) => {
      if (onSettledExtra) onSettledExtra(data, error, variables, context);
    }
  });
};