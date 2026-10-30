import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';

// Fuera de ['items'] y ['categories']: votar invalida esas claves (VOTE_INVALIDATE_KEYS) y la lista no debe
// recargarse a mitad del speed-rating (desaparecerían los ítems ya votados y se saltarían otros).
export const ONBOARDING_KEYS = {
  items: (slugs) => ['onboarding', 'items', slugs.join(',')],
};

// Ítems populares de las categorías elegidas para el speed-rating (F2-07).
export const useOnboardingItems = (slugs, enabled = true) => {
  return useQuery({
    queryKey: ONBOARDING_KEYS.items(slugs),
    queryFn: () => axiosClient.get('/onboarding/items', { params: { categories: slugs.join(',') } }),
    select: (response) => response.data ?? [],
    enabled: enabled && slugs.length > 0,
    staleTime: Infinity,
    refetchOnWindowFocus: false,
  });
};
