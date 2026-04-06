import { useQuery, keepPreviousData } from '@tanstack/react-query';
import { apiClient } from '../../lib/apiClient';
import { normalizePaginatedData } from '../../lib/apiUtils';

export const useAdminPaginatedQuery = ({ queryKey, endpoint, params = {}, enabled = true }) => {
    return useQuery({
        queryKey,
        enabled,
        placeholderData: keepPreviousData,
        queryFn: async () => {
            // Filtramos valores vacíos, nulos o indefinidos para evitar enviar query params sucios (ej: ?search=&status=)
            const cleanParams = Object.fromEntries(
                Object.entries(params).filter(([_, value]) => value !== '' && value !== undefined && value !== null)
            );

            const response = await apiClient.get(endpoint, { params: cleanParams });
            const responseData = response.data;

            return {
                data: normalizePaginatedData(responseData),
                meta: responseData?.meta ?? null,
                summary: responseData?.meta?.summary ?? null,
            };
        },
    });
};