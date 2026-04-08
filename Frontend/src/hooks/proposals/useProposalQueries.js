import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

export const PROPOSAL_KEYS = {
  all: ['proposals'],
  myProposals: () => [...PROPOSAL_KEYS.all, 'my-proposals'],
  details: () => [...PROPOSAL_KEYS.all, 'detail'],
  detail: (id) => [...PROPOSAL_KEYS.details(), id],
  adminLists: () => [...PROPOSAL_KEYS.all, 'admin-list'],
  adminList: (filters) => [...PROPOSAL_KEYS.adminLists(), { filters }],
  pending: () => [...PROPOSAL_KEYS.all, 'pending'],
};

export const useMyProposals = () => {
  return useQuery({
    queryKey: PROPOSAL_KEYS.myProposals(),
    queryFn: () => axiosClient.get('/proposals/my-proposals'),
  });
};

export const useProposal = (id) => {
  return useQuery({
    queryKey: PROPOSAL_KEYS.detail(id),
    queryFn: () => axiosClient.get(`/proposals/${id}`),
    enabled: !!id,
    select: (response) => response.data,
  });
};

// --- Consultas de Administrador ---
export const usePendingProposals = (filters = {}) => {
  return useQuery({
    queryKey: [...PROPOSAL_KEYS.pending(), { filters }],
    queryFn: () => axiosClient.get('/admin/proposals/pending', { params: filters }),
  });
};

export const useAdminProposals = (filters = {}) => {
  return useQuery({
    queryKey: PROPOSAL_KEYS.adminList(filters),
    queryFn: () => axiosClient.get('/admin/proposals', { params: filters }),
  });
};