import { useQuery } from '@tanstack/react-query';

export const useAdminQuery = ({ queryKey, queryFn, enabled = true, placeholderData }) => {
  return useQuery({
    queryKey,
    queryFn,
    enabled,
    placeholderData,
  });
};

