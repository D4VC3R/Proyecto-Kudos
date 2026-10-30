import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';

export const CATALOG_KEYS = {
  all: ['catalog'],
  search: (category, q) => ['catalog', 'search', category, q],
};

// Búsqueda en la fuente externa (GET /catalog/search). Solo se lanza a mano (enabled) y no se
// reintenta: el backend limita a 10 por minuto.
export const useCatalogSearch = ({ category, q } = {}, options = {}) => useQuery({
  queryKey: CATALOG_KEYS.search(category, q),
  queryFn: () => axiosClient.get('/catalog/search', { params: { category, q } }),
  select: (response) => response?.data ?? [],
  enabled: !!category && !!q,
  staleTime: 1000 * 60 * 10,
  retry: false,
  ...options,
});
