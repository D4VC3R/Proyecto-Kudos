import { useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { ADMIN_PROPOSAL_KEYS } from './useAdminProposalQueries';
import { ITEM_KEYS } from '../items/useItemQueries';
import { useBaseMutation } from '../common/useBaseMutation';

export const useReviewProposal = () => {
  const queryClient = useQueryClient();
  return useBaseMutation({
    mutationFn: ({ id, status, admin_notes }) =>
      axiosClient.patch(`/admin/proposals/${id}/review`, { status, admin_notes }),
    invalidateKeys: [ADMIN_PROPOSAL_KEYS.all],
    successMessage: 'Propuesta revisada correctamente',
    onSuccessExtra: (_, variables) => {
      if (variables.status === 'accepted') {
        queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });
      }
    }
  });
};
