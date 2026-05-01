import {keepPreviousData, useQuery} from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

export const ADMIN_USER_KEYS = {
  all: ['admin-users'],
  lists: () => [...ADMIN_USER_KEYS.all, 'list'],
  list: (filters) => [...ADMIN_USER_KEYS.lists(), { filters }],
  details: () => [...ADMIN_USER_KEYS.all, 'detail'],
  detail: (id) => [...ADMIN_USER_KEYS.details(), id],
};

export const useAdminUsers = (filters = {}) => {
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );

  return useQuery({
    queryKey: ADMIN_USER_KEYS.list(cleanFilters),
    queryFn: () => axiosClient.get('/admin/users', { params: cleanFilters }),
    placeholderData: keepPreviousData,
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