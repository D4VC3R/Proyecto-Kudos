import { useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';

export const useBaseMutation = ({
  mutationFn,
  invalidateKeys = [],
  successMessage,
  errorMessage = 'Ha ocurrido un error inesperado',
  onSuccessExtra,
  onErrorExtra,
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
  });
};

