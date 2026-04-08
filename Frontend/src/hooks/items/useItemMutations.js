import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { ITEM_KEYS } from './useItemQueries';

export const useAdminCreateItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (newItem) => axiosClient.post('/items', newItem),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.lists() });
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.adminLists() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useAdminUpdateItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/admin/items/${id}`, data),
    onSuccess: (response, variables) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.detail(variables.id) });
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.lists() });
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.adminLists() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useAdminModerateItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, status, reason }) =>
      axiosClient.patch(`/admin/items/${id}/moderate`, { status, reason }),
    onSuccess: (response, variables) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.detail(variables.id) });
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.adminLists() });
      // Si cambia el estado, puede que deje de estar visible en la app pública
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.lists() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useAdminDeleteItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => axiosClient.delete(`/items/${id}`),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};