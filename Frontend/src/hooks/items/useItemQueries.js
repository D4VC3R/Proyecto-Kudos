import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

// Query Key Factory: Única fuente de la verdad para las cachés de items
export const ITEM_KEYS = {
  all: ['items'],
  lists: () => [...ITEM_KEYS.all, 'list'],
  list: (filters) => [...ITEM_KEYS.lists(), { filters }],
  details: () => [...ITEM_KEYS.all, 'detail'],
  detail: (id) => [...ITEM_KEYS.details(), id],
  myItems: () => [...ITEM_KEYS.all, 'my-items'],
  adminLists: () => [...ITEM_KEYS.all, 'admin-list'],
  adminList: (filters) => [...ITEM_KEYS.adminLists(), { filters }],
};

export const useItems = (filters = {}) => {
  return useQuery({
    queryKey: ITEM_KEYS.list(filters),
    // Pasamos los filtros como query params
    queryFn: () => axiosClient.get('/items', { params: filters }),
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

export const useMyItems = () => {
  return useQuery({
    queryKey: ITEM_KEYS.myItems(),
    queryFn: () => axiosClient.get('/items/my-items'),
  });
};

// --- Consultas de Administrador ---
export const useAdminItems = (filters = {}) => {
  return useQuery({
    queryKey: ITEM_KEYS.adminList(filters),
    queryFn: () => axiosClient.get('/admin/items', { params: filters }),
  });
};