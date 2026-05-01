import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
export const useCreateCategory = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (newCategory) => axiosClient.post('/categories', newCategory),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};
export const useUpdateCategory = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ slug, data }) => axiosClient.put(`/categories/${slug}`, data),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};
export const useDeleteCategory = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (slug) => axiosClient.delete(`/categories/${slug}`),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};
