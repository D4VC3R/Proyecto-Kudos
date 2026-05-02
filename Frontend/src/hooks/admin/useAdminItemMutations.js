import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { ADMIN_ITEM_KEYS } from './useAdminItemQueries.js';
import { ITEM_KEYS } from '../items/useItemQueries.js';
import { useBaseMutation } from '../common/useBaseMutation';

export const useAdminCreateItem = () => {
  return useBaseMutation({
    mutationFn: (newItem) => axiosClient.post('/items', newItem),
    invalidateKeys: [ITEM_KEYS.lists(), ADMIN_ITEM_KEYS.lists()],
    successMessage: 'Ítem creado con éxito',
  });
};
export const useAdminUpdateItem = () => {
  const queryClient = useQueryClient();
  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/admin/items/${id}`, data),
    invalidateKeys: [ITEM_KEYS.lists(), ADMIN_ITEM_KEYS.lists()],
    successMessage: 'Ítem actualizado con éxito',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.detail(variables.id) });
    }
  });
};
export const useAdminModerateItem =  () => {
  const queryClient = useQueryClient();
  return useBaseMutation({
    mutationFn: ({ id, status, reason }) =>
      axiosClient.patch(`/admin/items/${id}/moderate`, { status, reason }),
    invalidateKeys: [ITEM_KEYS.lists(), ADMIN_ITEM_KEYS.lists()],
    successMessage: 'Ítem moderado con éxito',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.detail(variables.id) });
    }
  });
};
export const useAdminDeleteItem = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/items/${id}`),
    invalidateKeys: [ITEM_KEYS.all, ADMIN_ITEM_KEYS.all],
    successMessage: 'Ítem eliminado con éxito',
  });
};
