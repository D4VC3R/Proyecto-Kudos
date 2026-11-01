import axiosClient from '../../core/axiosClient.js';
import { useBaseMutation } from '../common/useBaseMutation';
import { LIST_KEYS, MY_LIST_KEYS } from './useListQueries';

// Mutaciones de las listas propias y del "me gusta" (F3-01). Invalidan "Mis listas" y las páginas públicas.
const INVALIDATE_KEYS = [MY_LIST_KEYS.all, LIST_KEYS.all];

export const useCreateMyList = () => {
  return useBaseMutation({
    mutationFn: (data) => axiosClient.post('/profile/lists', data),
    invalidateKeys: INVALIDATE_KEYS,
    successMessage: 'Lista creada',
  });
};

export const useUpdateMyList = () => {
  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/profile/lists/${id}`, data),
    invalidateKeys: INVALIDATE_KEYS,
    successMessage: 'Lista actualizada',
  });
};

export const useDeleteMyList = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/profile/lists/${id}`),
    invalidateKeys: INVALIDATE_KEYS,
    successMessage: 'Lista eliminada',
  });
};

export const useAddMyListItem = () => {
  return useBaseMutation({
    mutationFn: ({ listId, itemId, note }) =>
      axiosClient.post(`/profile/lists/${listId}/items`, { item_id: itemId, note: note || null }),
    invalidateKeys: INVALIDATE_KEYS,
    successMessage: 'Añadido a la lista',
  });
};

export const useRemoveMyListItem = () => {
  return useBaseMutation({
    mutationFn: ({ listId, itemId }) => axiosClient.delete(`/profile/lists/${listId}/items/${itemId}`),
    invalidateKeys: INVALIDATE_KEYS,
    successMessage: 'Quitado de la lista',
  });
};

export const useUpdateMyListItemNote = () => {
  return useBaseMutation({
    mutationFn: ({ listId, itemId, note }) =>
      axiosClient.patch(`/profile/lists/${listId}/items/${itemId}`, { note: note || null }),
    invalidateKeys: INVALIDATE_KEYS,
    successMessage: 'Nota guardada',
  });
};

// Sin toast de éxito: reordenar es un gesto continuo y el orden ya se ve en pantalla.
export const useReorderMyListItems = () => {
  return useBaseMutation({
    mutationFn: ({ listId, itemIds }) =>
      axiosClient.put(`/profile/lists/${listId}/items/order`, { item_ids: itemIds }),
    invalidateKeys: INVALIDATE_KEYS,
  });
};

export const useLikeList = () => {
  return useBaseMutation({
    mutationFn: (listId) => axiosClient.post(`/lists/${listId}/like`),
    invalidateKeys: [LIST_KEYS.all],
  });
};

export const useUnlikeList = () => {
  return useBaseMutation({
    mutationFn: (listId) => axiosClient.delete(`/lists/${listId}/like`),
    invalidateKeys: [LIST_KEYS.all],
  });
};
