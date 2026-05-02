import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { ADMIN_USER_KEYS } from './useAdminUserQueries';
import { useBaseMutation } from '../common/useBaseMutation';

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