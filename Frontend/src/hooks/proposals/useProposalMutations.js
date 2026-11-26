import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
import { PROPOSAL_KEYS } from './useProposalQueries';
import { useBaseMutation } from '../common/useBaseMutation';

// Acepta `{setError}` (el de react-hook-form) para marcar en el formulario los campos que el backend rechaza con un 422.
export const useCreateProposal = ({ setError } = {}) => {
  return useBaseMutation({
    mutationFn: (data) => axiosClient.post('/proposals', data),
    setError,
    invalidateKeys: [PROPOSAL_KEYS.myProposals()],
    successMessage: 'Propuesta creada',
  });
};

// Igual que useCreateProposal: `{setError}` marca los campos de un 422.
export const useUpdateProposal = ({ setError } = {}) => {
  const queryClient = useQueryClient();

  return useBaseMutation({
    mutationFn: ({ id, data }) => axiosClient.put(`/proposals/${id}`, data),
    setError,
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
