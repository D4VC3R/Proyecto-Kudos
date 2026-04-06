import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes, useAdminMutation } from '../adminQueryBase';
import { useAdminPaginatedQuery } from '../useAdminPaginatedQuery';

export const useAdminUsersQuery = (params) => {
  return useAdminPaginatedQuery({
    queryKey: adminQueryKeys.users(params),
    endpoint: '/admin/users',
    params: {
      page: params.page,
      per_page: params.perPage,
      sort_by: params.sortBy,
      sort_direction: params.sortDirection,
      search: params.search,
      ban_state: params.banState,
      role: params.role,
    },
  });
};

export const useAdminUsersSummaryQuery = () => {
  return useQuery({
    queryKey: adminQueryKeys.usersSummary(),
    queryFn: async () => {
      const response = await apiClient.get('/admin/users', {
        params: { page: 1, per_page: 1 },
      });
      return response.data;
    },
  });
};

export const useAdminUserDetailQuery = ({ userId }) => {
  return useQuery({
    queryKey: adminQueryKeys.userDetail(userId),
    enabled: Boolean(userId),
    queryFn: async () => {
      const response = await apiClient.get(`/admin/users/${userId}`);
      return response.data;
    },
  });
};

export const useAdminBanUserMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ userId, payload }) => {
      const response = await apiClient.patch(`/admin/users/${userId}/ban`, payload);
      return response.data;
    },
    defaultSuccessMessage: 'Usuario baneado correctamente.',
    invalidateQueryKeys: [adminQueryScopes.users, adminQueryScopes.userDetail],
  });
};

export const useAdminUnbanUserMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ userId }) => {
      const response = await apiClient.patch(`/admin/users/${userId}/unban`);
      return response.data;
    },
    defaultSuccessMessage: 'Usuario desbaneado correctamente.',
    invalidateQueryKeys: [adminQueryScopes.users, adminQueryScopes.userDetail],
  });
};

export const useAdminRevokeSessionsMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ userId }) => {
      const response = await apiClient.post(`/admin/users/${userId}/sessions/revoke`);
      return response.data;
    },
    defaultSuccessMessage: 'Sesiones revocadas correctamente.',
    invalidateQueryKeys: [adminQueryScopes.users, adminQueryScopes.userDetail],
  });
};