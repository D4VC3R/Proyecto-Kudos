import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

const CATEGORIES_KEYS = {
  all: ['categories'],
  detail: (slug) => ['categories', slug],
  ranking: (slug) => ['categories', slug, 'ranking'],
  nextItem: (slug) => ['categories', slug, 'next-item'],
};

export const useCategories = () => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.all,
    queryFn: () => axiosClient.get('/categories'),
    select: (response) => response.data,
  });
};

export const useCategoryRanking = (categorySlug) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.ranking(categorySlug),
    queryFn: () => axiosClient.get(`/categories/${categorySlug}/ranking`),
    select: (response) => {
      return response.data?.ranking || [];
    },
    enabled: !!categorySlug,
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
  });
};