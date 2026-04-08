import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

const CATEGORIES_KEYS = {
  all: ['categories'],
  detail: (id) => ['categories', id],
  ranking: (id) => ['categories', id, 'ranking'],
  nextItem: (id) => ['categories', id, 'next-item'],
};

export const useCategories = () => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.all,
    queryFn: () => axiosClient.get('/categories'),
    select: (response) => response.data,
  });
};

export const useCategoryRanking = (categoryId) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.ranking(categoryId),
    queryFn: () => axiosClient.get(`/categories/${categoryId}/ranking`),
    select: (response) => response.data,
    enabled: !!categoryId,
  });
};

export const useNextCategoryItem = (categoryId) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.nextItem(categoryId),
    queryFn: () => axiosClient.get(`/categories/${categoryId}/next-item`),
    // Si nextItem devuelve 204 No Content, axios devolverá null/undefined
    select: (response) => response ? { data: response.data, meta: response.meta } : null,
    enabled: !!categoryId,
  });
};