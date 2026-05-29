import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { PROPOSAL_KEYS } from './useProposalQueries';
import { useBaseMutation } from '../common/useBaseMutation';

export const useCreateProposal = () => {
  return useBaseMutation({
    mutationFn: (data) => axiosClient.post('/proposals', data),
    invalidateKeys: [PROPOSAL_KEYS.myProposals()],
    successMessage: 'Propuesta creada',
  });
};

export const useUpdateProposal = () => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/proposals/${id}`, data),
    invalidateKeys: [PROPOSAL_KEYS.myProposals()],
    successMessage: 'Propuesta actualizada',
    onSuccessExtra: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: PROPOSAL_KEYS.detail(variables.id) });
    }
  });
};

export const useDeleteProposal = () => {
  return useBaseMutation({
    mutationFn: (id) => axiosClient.delete(`/proposals/${id}`),
    invalidateKeys: [PROPOSAL_KEYS.myProposals()],
    successMessage: 'Propuesta eliminada',
  });
};
