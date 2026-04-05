import { keepPreviousData } from '@tanstack/react-query';
import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes } from '../adminQueryKeys';
import { useAdminMutation } from '../useAdminMutation';
import { useAdminQuery } from '../useAdminQuery';

export const adminUsersQueryKey = adminQueryKeys.users;

export const useAdminUsersQuery = ({ page, search, banState, role, perPage, sortBy, sortDirection }) => {
  return useAdminQuery({
    queryKey: adminUsersQueryKey({ page, search, banState, role, perPage, sortBy, sortDirection }),
    placeholderData: keepPreviousData,
    queryFn: async () => {
      const params = {
        page,
        per_page: perPage,
        sort_by: sortBy,
        sort_direction: sortDirection,
      };

      if (search) {
        params.search = search;
      }

      if (banState) {
        params.ban_state = banState;
      }

      if (role) {
        params.role = role;
      }

      const response = await apiClient.get('/admin/users', { params });
      return response.data;
    },
  });
};

export const useAdminUsersSummaryQuery = () => {
  return useAdminQuery({
    queryKey: adminQueryKeys.usersSummary(),
    queryFn: async () => {
      const response = await apiClient.get('/admin/users', {
        params: {
          page: 1,
          per_page: 1,
        },
      });

      return response.data;
    },
  });
};

export const useAdminUserDetailQuery = ({ userId }) => {
  return useAdminQuery({
    queryKey: adminQueryKeys.userDetail({ userId }),
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
      const response = await apiClient.patch(`/admin/users/${userId}/ban`, payload, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      return response.data;
    },
    defaultSuccessMessage: 'Usuario baneado correctamente.',
    invalidateQueryKeys: [adminQueryScopes.users],
  });
};

export const useAdminUnbanUserMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ userId }) => {
      const response = await apiClient.patch(`/admin/users/${userId}/unban`);
      return response.data;
    },
    defaultSuccessMessage: 'Usuario desbaneado correctamente.',
    invalidateQueryKeys: [adminQueryScopes.users],
  });
};

export const useAdminRevokeSessionsMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ userId }) => {
      const response = await apiClient.post(`/admin/users/${userId}/sessions/revoke`);
      return response.data;
    },
    defaultSuccessMessage: 'Sesiones revocadas correctamente.',
    invalidateQueryKeys: [adminQueryScopes.users],
  });
};

