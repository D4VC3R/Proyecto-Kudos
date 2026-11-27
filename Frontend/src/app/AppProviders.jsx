import { QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import { AppErrorBoundary } from '../core/AppErrorBoundary.jsx';
import { queryClient } from '../core/queryClient.js';
import { useTheme } from '../hooks/common/useTheme.js';

/**
 * Proveedores globales de la aplicación (errores, alertas toast, React Query)
 * */
export const AppProviders = ({ children }) => {
  // Mantiene sincronizados el tema del sistema, las pestañas y theme-color aunque ningún componente muestre el selector.
  useTheme();

  return (
    <AppErrorBoundary>
      <QueryClientProvider client={queryClient}>
        {children}
        <Toaster position="bottom-center" toastOptions={{ duration: 3000 }} />
      </QueryClientProvider>
    </AppErrorBoundary>
  );
};