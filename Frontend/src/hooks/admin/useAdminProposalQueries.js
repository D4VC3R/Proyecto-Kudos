import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

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
