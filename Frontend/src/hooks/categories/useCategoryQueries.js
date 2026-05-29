import {useQuery, useInfiniteQuery, keepPreviousData} from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';
/*
* Hook personalizado para consumir los datos relativos a las categorías.
* Utiliza una instancia del cliente axios ya configurada para las peticiones y react-query para el manejo de datos y caché.
* */

// Claves de caché, react-query guarda los datos en caché bajo la clave correspondiente, evitando recargas innecesarias y permitiendo compartir datos entre componentes que usen la misma clave.
// Por eso no se necesitan Proveedores específicos para cada hook, basta con usar las mismas claves.
const CATEGORIES_KEYS = {
  all: ['categories'],
  detail: (slug) => ['categories', slug],
  ranking: (slug, page, limit) => ['categories', slug, 'ranking', page, limit],
  infiniteRanking: (slug) => ['categories', slug, 'ranking', 'infinite'],
  nextItem: (slug) => ['categories', slug, 'next-item'],
};

// Hook para obtener la lista de categorías. Devuelve un array de categorías con su información básica (id, name, description, slug).
export const useCategories = () => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.all,
    queryFn: () => axiosClient.get('/categories'),
    select: (response) => response.data, // De la respuesta nos quedamos con data, que es el array de categorías.
    placeholderData: keepPreviousData,
    staleTime: 1000 * 60 * 60 * 24
  });
};

// Hook para recuperar los datos del ranking de 10 en 10, pasándo automáticamente a la siguiente página mientras queden registros.
export const useInfiniteCategoryRanking = (categorySlug, perPage = 10) => {
  return useInfiniteQuery({
    queryKey: CATEGORIES_KEYS.infiniteRanking(categorySlug),
    queryFn: ({pageParam}) =>
      axiosClient.get(`/categories/${categorySlug}/ranking`, {params: {page: pageParam, per_page: perPage}}),
    initialPageParam: 1, // Empezando por la 1, va recuperando las siguientes páginas automáticamente con getNextPageParam.
    getNextPageParam: (lastPage) => {
      if (!lastPage.meta) return undefined;
      return lastPage.meta.current_page < lastPage.meta.last_page
        ? lastPage.meta.current_page + 1
        : undefined;
    },
    enabled: !!categorySlug, // Sirve para evitar que la consulta se ejecute si no tenemos un slug de categoría válido, previniendo errores.
    placeholderData: keepPreviousData, // Útil para mantener los datos anteriores mientras se cargan los nuevos, evitando parpadeos en la UI.
    staleTime: 1000 * 60 * 5, // 5 minutos de caché, ya que el ranking puede cambiar con cierta frecuencia pero no es necesario recargarlo constantemente.
  });
};

// Llama al siguiente item mientras el backend no devuelva un null.
export const useNextCategoryItem = (categorySlug) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.nextItem(categorySlug),
    queryFn: () => axiosClient.get(`/categories/${categorySlug}/next-item`),
    select: (response) => response ? {data: response.data, meta: response.meta} : null,
    enabled: !!categorySlug,
  });
};

// Información detallada de la categoría. Mismo funcionamiento que el resto de hooks.
export const useCategoryDetail = (categorySlug) => {
  return useQuery({
    queryKey: CATEGORIES_KEYS.detail(categorySlug),
    queryFn: () => axiosClient.get(`/categories/${categorySlug}`),
    select: (response) => response.data,
    enabled: !!categorySlug,
    placeholderData: keepPreviousData,
  });
};