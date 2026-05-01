import { useMutation, useQueryClient } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import toast from 'react-hot-toast';
import { ADMIN_PROPOSAL_KEYS } from './useAdminProposalQueries';
import { ITEM_KEYS } from '../items/useItemQueries';
export const useReviewProposal = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, status, admin_notes }) =>
      axiosClient.patch(`/admin/proposals/${id}/review`, { status, admin_notes }),
    onSuccess: (response, variables) => {
      if (variables.status === 'accepted') {
        queryClient.invalidateQueries({ queryKey: ITEM_KEYS.all });
      }
      queryClient.invalidateQueries({ queryKey: ADMIN_PROPOSAL_KEYS.all });
      toast.success(response.message);
    },
    onError: (error) => toast.error(error.message),
  });
};
