import { QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import { AppErrorBoundary } from '../core/AppErrorBoundary.jsx';
import { queryClient } from '../core/queryClient.js';


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