import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import { AppErrorBoundary } from '../components/common/AppErrorBoundary';

// Configuración global de React Query. Desactivamos refetchOnWindowFocus para evitar recargas innecesarias al cambiar de pestaña, y limitamos a 1 el número de reintentos en caso de error.
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

// Proveedores globales de la aplicación (errores, alertas toast, React Query)
export const AppProviders = ({ children }) => {
  return (
    <AppErrorBoundary>
      <QueryClientProvider client={queryClient}>
        {children}
        <Toaster position="bottom-center" toastOptions={{ duration: 3000 }} />
      </QueryClientProvider>
    </AppErrorBoundary>
  );
};