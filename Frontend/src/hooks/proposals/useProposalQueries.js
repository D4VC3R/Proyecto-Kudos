import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
export const PROPOSAL_KEYS = {
  all: ['proposals'],
  myProposals: () => [...PROPOSAL_KEYS.all, 'my-proposals'],
  details: () => [...PROPOSAL_KEYS.all, 'detail'],
  detail: (id) => [...PROPOSAL_KEYS.details(), id],
};
export const useMyProposals = () => {
  return useQuery({
    queryKey: PROPOSAL_KEYS.myProposals(),
    queryFn: () => axiosClient.get('/proposals/my-proposals'),
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 5,
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
