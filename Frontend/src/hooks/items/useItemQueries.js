import {useQuery, useInfiniteQuery, keepPreviousData} from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

// Query Key Factory: Única fuente de la verdad para las cachés de items
export const ITEM_KEYS = {
  all: ['items'],
  lists: () => [...ITEM_KEYS.all, 'list'],
  list: (filters) => [...ITEM_KEYS.lists(), { filters }],
  infiniteLists: () => [...ITEM_KEYS.all, 'infinite-list'],
  infiniteList: (filters) => [...ITEM_KEYS.infiniteLists(), { filters }],
  details: () => [...ITEM_KEYS.all, 'detail'],
  detail: (id) => [...ITEM_KEYS.details(), id],
  myItems: () => [...ITEM_KEYS.all, 'my-items'],
};

export const useItems = (filters = {}, options = {}) => {
  return useQuery({
    queryKey: ITEM_KEYS.list(filters),
    // Pasamos los filtros como query params
    queryFn: () => axiosClient.get('/items', { params: filters }),
    ...options,
  });
};

export const useInfiniteItems = (filters = {}, options = {}) => {
  return useInfiniteQuery({
    queryKey: ITEM_KEYS.infiniteList(filters),
    queryFn: ({ pageParam = 1 }) =>
      axiosClient.get('/items', { params: { ...filters, page: pageParam } }),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage.meta) return undefined;
      return lastPage.meta.has_more
        ? lastPage.meta.current_page + 1
        : undefined;
    },
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 60, // 1 hora de caché
    ...options,
  });
};

export const useItem = (id) => {
  return useQuery({
    queryKey: ITEM_KEYS.detail(id),
    queryFn: () => axiosClient.get(`/items/${id}`),
    enabled: !!id, // No ejecuta la petición si no hay ID
    select: (response) => response.data,
  });
};

export const useItemComments = (id) => {
  return useQuery({
    queryKey: [...ITEM_KEYS.detail(id), 'comments'],
    queryFn: () => axiosClient.get(`/items/${id}/comments`),
    enabled: !!id,
    select: (response) => response.data || [],
  });
};

export const useMyItems = () => {
  return useQuery({
    queryKey: ITEM_KEYS.myItems(),
    queryFn: () => axiosClient.get('/items/my-items'),
  });
};
