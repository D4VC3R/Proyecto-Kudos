import { useQuery, useInfiniteQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

const CATEGORIES_KEYS = {
  all: ['categories'],
  detail: (slug) => ['categories', slug],
  ranking: (slug, page, limit) => ['categories', slug, 'ranking', page, limit],
  infiniteRanking: (slug) => ['categories', slug, 'ranking', 'infinite'],
  nextItem: (slug) => ['categories', slug, 'next-item'],
};

export const useCategories = () => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.all,
    queryFn: () => axiosClient.get('/categories'),
    select: (response) => response.data,
  });
};

export const useCategoryRanking = (categorySlug, page = 1, perPage = 10) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.ranking(categorySlug, page, perPage),
    queryFn: () => axiosClient.get(`/categories/${categorySlug}/ranking`, { params: { page, per_page: perPage } }),
    select: (response) => {
      // response includes .data and .meta
      return response;
    },
    enabled: !!categorySlug,
    placeholderData: keepPreviousData,
  });
};

export const useInfiniteCategoryRanking = (categorySlug, perPage = 10) => {
  return useInfiniteQuery({
    queryKey: CATEGORIES_KEYS.infiniteRanking(categorySlug),
    queryFn: ({ pageParam }) =>
      axiosClient.get(`/categories/${categorySlug}/ranking`, { params: { page: pageParam, per_page: perPage } }),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage.meta) return undefined;
      return lastPage.meta.current_page < lastPage.meta.last_page
        ? lastPage.meta.current_page + 1
        : undefined;
    },
    enabled: !!categorySlug,
    placeholderData: keepPreviousData,
  });
};

export const useNextCategoryItem = (categorySlug) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.nextItem(categorySlug),
    queryFn: () => axiosClient.get(`/categories/${categorySlug}/next-item`),
    // Si nextItem devuelve 204 No Content, axios devolverá null/undefined
    select: (response) => response ? { data: response.data, meta: response.meta } : null,
    enabled: !!categorySlug,
  });
};

export const useCategoryDetail = (categorySlug) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.detail(categorySlug),
    queryFn: () => axiosClient.get(`/categories/${categorySlug}`),
    // Extraemos la 'data' del envoltorio de la respuesta axios
    select: (response) => response.data,
    enabled: !!categorySlug,
    placeholderData: keepPreviousData,
  });
};