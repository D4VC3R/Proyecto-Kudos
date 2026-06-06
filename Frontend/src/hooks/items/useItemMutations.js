import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { ITEM_KEYS } from './useItemQueries';
import { useBaseMutation } from '../common/useBaseMutation';

/**
 * Hooks personalizados para manejar las mutaciones relacionadas con los comentarios de los ítems.
 * */
// Guardar un comentario nuevo para un ítem específico. Al finalizar, invalida la consulta de los comentarios del ítem para mostrar el nuevo comentario.
export const useCreateComment = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ itemId, content }) => axiosClient.post(`/items/${itemId}/comments`, { content }),
    successMessage: 'Comentario publicado',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: [...ITEM_KEYS.detail(variables.itemId), 'comments'] });
    }
  });
};
// Actualizar el contenido de un comentario existente. También invalida las keys.
export const useUpdateComment = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ id, content }) => axiosClient.put(`/comments/${id}`, { content }),
    successMessage: 'Comentario actualizado',
    onSuccessExtra: (_, variables) => {
      if (variables.itemId) {
        queryClient.invalidateQueries({ queryKey: [...ITEM_KEYS.detail(variables.itemId), 'comments'] });
      }
    }
  });
};

// Borrar un comentario e invalida las keys para reflejar el cambio.
export const useDeleteComment = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ id }) => axiosClient.delete(`/comments/${id}`),
    successMessage: 'Comentario eliminado',

    onSuccessExtra: (_, variables) => {
      if (variables.itemId) {
        queryClient.invalidateQueries({ queryKey: [...ITEM_KEYS.detail(variables.itemId), 'comments'] });
      }
    }
  });
};
