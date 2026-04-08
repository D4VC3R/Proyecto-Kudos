import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

export const ADMIN_USER_KEYS = {
  all: ['admin-users'],
  lists: () => [...ADMIN_USER_KEYS.all, 'list'],
  list: (filters) => [...ADMIN_USER_KEYS.lists(), { filters }],
  details: () => [...ADMIN_USER_KEYS.all, 'detail'],
  detail: (id) => [...ADMIN_USER_KEYS.details(), id],
};

export const useAdminUsers = (filters = {}) => {
  return useQuery({
    queryKey: ADMIN_USER_KEYS.list(filters),
    queryFn: () => axiosClient.get('/admin/users', { params: filters }),
  });
};

export const useAdminUserDetail = (userId) => {
  return useQuery({
    queryKey: ADMIN_USER_KEYS.detail(userId),
    queryFn: () => axiosClient.get(`/admin/users/${userId}`),
    enabled: !!userId,
    select: (response) => response.data,
  });
};