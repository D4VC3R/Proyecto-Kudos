import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { PROPOSAL_KEYS } from './useProposalQueries';

export const useCreateProposal = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data) => axiosClient.post('/proposals', data),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: PROPOSAL_KEYS.myProposals() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useUpdateProposal = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/proposals/${id}`, data),
    onSuccess: (response, variables) => {
      queryClient.invalidateQueries({ queryKey: PROPOSAL_KEYS.detail(variables.id) });
      queryClient.invalidateQueries({ queryKey: PROPOSAL_KEYS.myProposals() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

export const useDeleteProposal = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => axiosClient.delete(`/proposals/${id}`),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: PROPOSAL_KEYS.myProposals() });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};

