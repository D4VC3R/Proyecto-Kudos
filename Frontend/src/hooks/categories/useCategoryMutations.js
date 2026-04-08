import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';

export const useCreateCategory = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (newCategory) => axiosClient.post('/categories', newCategory),
    onSuccess: (response) => {
      // Invalida la lista para que se actualice la vista automáticamente
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};