import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { ADMIN_USER_KEYS } from './useAdminUserQueries';

export const useBanUser = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ userId, reason, days, is_permanent }) =>
      axiosClient.patch(`/admin/users/${userId}/ban`, { reason, days, is_permanent }),
    onSuccess: (response, variables) => {
      queryClient.invalidateQueries({ queryKey: ADMIN_USER_KEYS.detail(variables.userId) });
      queryClient.invalidateQueries({ queryKey: ADMIN_USER_KEYS.lists() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useUnbanUser = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (userId) => axiosClient.patch(`/admin/users/${userId}/unban`),
    onSuccess: (response, variables) => {
      queryClient.invalidateQueries({ queryKey: ADMIN_USER_KEYS.detail(variables) });
      queryClient.invalidateQueries({ queryKey: ADMIN_USER_KEYS.lists() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useRevokeUserSessions = () => {
  return useMutation({
    mutationFn: (userId) => axiosClient.post(`/admin/users/${userId}/sessions/revoke`),
    onSuccess: (response) => {
      toast.success(response.message || 'Sesiones revocadas correctamente');
    },
    onError: (error) => toast.error(error.message),
  });
};