import { apiClient } from '../../../lib/apiClient';
import { adminQueryKeys, adminQueryScopes } from '../adminQueryKeys';
import { useAdminMutation } from '../useAdminMutation';
import { useAdminQuery } from '../useAdminQuery';

export const adminProposalsQueryKey = adminQueryKeys.proposals;

const normalizeProposalsData = (responseData) => {
  const rawData = responseData?.data;

  if (Array.isArray(rawData)) {
    return rawData;
  }

  if (rawData && Array.isArray(rawData.data)) {
    return rawData.data;
  }

  return [];
};

export const useAdminProposalsQuery = ({ page, search, status, perPage }) => {
  return useAdminQuery({
    queryKey: adminProposalsQueryKey({ page, search, status, perPage }),
    queryFn: async () => {
      const params = {
        page,
        per_page: perPage,
      };

      if (search) {
        params.search = search;
      }

      if (status) {
        params.status = status;
      }

      const response = await apiClient.get('/admin/proposals', { params });
      const responseData = response.data;

      return {
        proposals: normalizeProposalsData(responseData),
        meta: responseData?.meta ?? null,
      };
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

      const response = await apiClient.patch(`/admin/proposals/${proposalId}/review`, payload, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      return response.data;
    },
    defaultSuccessMessage: 'Propuesta revisada correctamente.',
    invalidateQueryKeys: [adminQueryScopes.proposals],
  });
};

