import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

export const COMMENT_KEYS = {
  all: ['comments'],
  byItem: (itemId, filters) => [...COMMENT_KEYS.all, 'item', itemId, { filters }],
};

export const useItemComments = (itemId, filters = {}) => {
  return useQuery({
    queryKey: COMMENT_KEYS.byItem(itemId, filters),
    queryFn: () => axiosClient.get(`/items/${itemId}/comments`, { params: filters }),
    enabled: !!itemId,
  });
};