import axiosClient from '../../lib/axiosClient';
import { useBaseMutation } from '../common/useBaseMutation';

export const useCreateCategory = () => {
  return useBaseMutation({
    mutationFn: (newCategory) => axiosClient.post('/categories', newCategory),
    invalidateKeys: [['categories']],
    successMessage: 'Categoría creada correctamente',
  });
};
export const useUpdateCategory = () => {
  return useBaseMutation({
    mutationFn: ({ slug, data }) => axiosClient.put(`/categories/${slug}`, data),
    invalidateKeys: [['categories']],
    successMessage: 'Categoría actualizada correctamente',
  });
};
export const useDeleteCategory = () => {
  return useBaseMutation({
    mutationFn: (slug) => axiosClient.delete(`/categories/${slug}`),
    invalidateKeys: [['categories']],
    successMessage: 'Categoría eliminada correctamente',
  });
};
