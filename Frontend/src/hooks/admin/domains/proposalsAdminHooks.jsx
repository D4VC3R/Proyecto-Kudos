import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes, useAdminMutation } from '../adminQueryBase';
import { useAdminPaginatedQuery } from '../useAdminPaginatedQuery';

export const useAdminProposalsQuery = (params) => {
  return useAdminPaginatedQuery({
    queryKey: adminQueryKeys.proposals(params),
    endpoint: '/admin/proposals',
    params: {
      page: params.page,
      per_page: params.perPage,
      search: params.search,
      status: params.status,
    },
  });
};

export const useAdminReviewProposalMutation = () => {
  return useAdminMutation({
    mutationFn: async ({ proposalId, status, adminNotes }) => {
      const payload = { status };
      if (adminNotes) {
        payload.admin_notes = adminNotes;
      }
      const response = await apiClient.patch(`/admin/proposals/${proposalId}/review`, payload);
      return response.data;
    },
    defaultSuccessMessage: 'Propuesta revisada correctamente.',
    invalidateQueryKeys: [adminQueryScopes.proposals],
  });
};