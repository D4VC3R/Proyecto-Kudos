import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';

// --- ITEMS ---
export const ADMIN_ITEM_KEYS = {
  all: ['admin-items'],
  lists: () => [...ADMIN_ITEM_KEYS.all, 'list'],
  list: (filters) => [...ADMIN_ITEM_KEYS.lists(), { filters }],
};
export const useAdminItems = (filters = {}) => {
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );

  return useQuery({
    queryKey: ADMIN_ITEM_KEYS.list(cleanFilters),
    queryFn: () => axiosClient.get('/admin/items', { params: cleanFilters }),
    placeholderData: keepPreviousData,
  });
};

// --- PROPUESTAS ---
export const ADMIN_PROPOSAL_KEYS = {
  all: ['admin-proposals'],
  lists: () => [...ADMIN_PROPOSAL_KEYS.all, 'list'],
  list: (filters) => [...ADMIN_PROPOSAL_KEYS.lists(), { filters }],
  pending: () => [...ADMIN_PROPOSAL_KEYS.all, 'pending'],
};
export const usePendingProposals = (filters = {}) => {
  return useQuery({
    queryKey: [...ADMIN_PROPOSAL_KEYS.pending(), { filters }],
    queryFn: () => axiosClient.get('/admin/proposals/pending', { params: filters }),
    placeholderData: keepPreviousData,
  });
};
export const useAdminProposals = (filters = {}) => {
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );

  return useQuery({
    queryKey: ADMIN_PROPOSAL_KEYS.list(cleanFilters),
    queryFn: () => axiosClient.get('/admin/proposals', { params: cleanFilters }),
    placeholderData: keepPreviousData,
  });
};

// --- USUARIOS ---
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
