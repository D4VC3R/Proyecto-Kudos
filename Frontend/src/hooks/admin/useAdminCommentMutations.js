import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { ITEM_KEYS } from '../items/useItemQueries.js';
export const useAdminHideComment = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, reason }) => axiosClient.patch(`/admin/comments/${id}/hide`, { reason }),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};
export const useAdminUnhideComment = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id) => axiosClient.patch(`/admin/comments/${id}/unhide`),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};
