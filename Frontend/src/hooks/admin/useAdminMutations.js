import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { useBaseMutation } from '../common/useBaseMutation';
import { ITEM_KEYS } from '../items/useItemQueries';
import { HOME_KEYS } from '../home/useHomeQueries';
import { LIST_KEYS } from '../lists/useListQueries';
import { ADMIN_ITEM_KEYS, ADMIN_LIST_KEYS, ADMIN_PROPOSAL_KEYS, ADMIN_USER_KEYS } from './useAdminQueries';

// --- CATEGORIAS ---
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

// --- COMENTARIOS ---
export const useAdminHideComment = () => {
  return useBaseMutation({
    mutationFn: ({ id, reason }) => axiosClient.patch(`/admin/comments/${id}/hide`, { reason }),
    invalidateKeys: [ITEM_KEYS.all],
    successMessage: 'Comentario ocultado',
  });
};
export const useAdminUnhideComment = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.patch(`/admin/comments/${id}/unhide`),
    invalidateKeys: [ITEM_KEYS.all],
    successMessage: 'Comentario restaurado',
  });
};

// --- ITEMS ---
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

// --- PROPUESTAS ---
export const useReviewProposal = () => {
  const queryClient = useQueryClient();
  return useBaseMutation({
    mutationFn: ({ id, status, admin_notes }) =>
      axiosClient.patch(`/admin/proposals/${id}/review`, { status, admin_notes }),
    invalidateKeys: [ADMIN_PROPOSAL_KEYS.all],
    successMessage: 'Propuesta revisada correctamente',
    onSuccessExtra: (_, variables) => {
      if (variables.status === 'accepted') {
        queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });
      }
    }
  });
};

// --- USUARIOS ---
export const useBanUser = () => {
  const queryClient = useQueryClient();
  return useBaseMutation({
    mutationFn: ({ userId, reason, days, is_permanent }) =>
      axiosClient.patch(`/admin/users/${userId}/ban`, { reason, days, is_permanent }),
    invalidateKeys: [ADMIN_USER_KEYS.lists()],
    successMessage: 'Usuario suspendido con éxito',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ADMIN_USER_KEYS.detail(variables.userId) });
    }
  });
};
export const useUnbanUser = () => {
  const queryClient = useQueryClient();
  return useBaseMutation({
    mutationFn: (userId) => axiosClient.patch(`/admin/users/${userId}/unban`),
    invalidateKeys: [ADMIN_USER_KEYS.lists()],
    successMessage: 'Usuario restaurado con éxito',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ADMIN_USER_KEYS.detail(variables) });
    }
  });
};
export const useRevokeUserSessions = () => {
  return useBaseMutation({
    mutationFn: (userId) => axiosClient.post(`/admin/users/${userId}/sessions/revoke`),
    successMessage: 'Sesiones revocadas correctamente',
  });
};

// --- LISTAS EDITORIALES ---
const LIST_INVALIDATE_KEYS = [ADMIN_LIST_KEYS.all, HOME_KEYS.all, LIST_KEYS.all];
export const useAdminCreateList = () => {
  return useBaseMutation({
    mutationFn: (data) => axiosClient.post('/admin/lists', data),
    invalidateKeys: LIST_INVALIDATE_KEYS,
    successMessage: 'Lista creada',
  });
};
export const useAdminUpdateList = () => {
  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/admin/lists/${id}`, data),
    invalidateKeys: LIST_INVALIDATE_KEYS,
    successMessage: 'Lista actualizada',
  });
};
export const useAdminDeleteList = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/admin/lists/${id}`),
    invalidateKeys: LIST_INVALIDATE_KEYS,
    successMessage: 'Lista eliminada',
  });
};
export const useAdminAddListItem = () => {
  return useBaseMutation({
    mutationFn: ({ listId, itemId, note }) => axiosClient.post(`/admin/lists/${listId}/items`, { item_id: itemId, note }),
    invalidateKeys: LIST_INVALIDATE_KEYS,
    successMessage: 'Añadido a la lista',
  });
};
export const useAdminRemoveListItem = () => {
  return useBaseMutation({
    mutationFn: ({ listId, itemId }) => axiosClient.delete(`/admin/lists/${listId}/items/${itemId}`),
    invalidateKeys: LIST_INVALIDATE_KEYS,
    successMessage: 'Quitado de la lista',
  });
};
// Sin mensaje de éxito: se reordena a menudo.
export const useAdminReorderListItems = () => {
  return useBaseMutation({
    mutationFn: ({ listId, itemIds }) => axiosClient.put(`/admin/lists/${listId}/items/order`, { item_ids: itemIds }),
    invalidateKeys: LIST_INVALIDATE_KEYS,
  });
};
