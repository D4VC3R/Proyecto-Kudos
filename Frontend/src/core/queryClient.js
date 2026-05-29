import { QueryClient } from '@tanstack/react-query';

/** Configuración global del cliente de React Query, con opciones predeterminadas para todas las consultas.
 * - `refetchOnWindowFocus: false` evita que las consultas se vuelvan a ejecutar automáticamente al enfocar la ventana.
 * - `retry: 1` permite un reintento automático en caso de error, pero solo una vez.
 * - `staleTime: 1000 * 60` marca los datos como "frescos" durante 1 minuto, evitando refetch innecesarios (Se puede sobreescribir).
 */

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
      staleTime: 1000 * 60,
    },
  },
});