import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { useApiErrorHandler } from '../useApiErrorHandler';

export const useAdminMutation = ({ mutationFn, defaultSuccessMessage, invalidateQueryKeys = [] }) => {
  const queryClient = useQueryClient();
  const { handleApiError } = useApiErrorHandler();

  return useMutation({
    mutationFn,
    onSuccess: async (responseData) => {
      toast.success(responseData?.message ?? defaultSuccessMessage);

      await Promise.all(
        invalidateQueryKeys.map((queryKey) => {
          return queryClient.invalidateQueries({ queryKey });
        }),
      );
    },
    onError: (error) => {
      handleApiError({ error, notify: (message) => toast.error(message) });
    },
  });
};

