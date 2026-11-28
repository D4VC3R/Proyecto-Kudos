import { MotionConfig } from 'framer-motion';
import { QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import { AppErrorBoundary } from '../core/AppErrorBoundary.jsx';
import { queryClient } from '../core/queryClient.js';
import { useTheme } from '../hooks/common/useTheme.js';

// react-hot-toast pone el fondo y el color en línea, así que los tokens van en `style` con las variables CSS
// (cambian con la paleta y el modo).
const TOAST_OPTIONS = {
  duration: 3000,
  style: {
    background: 'rgb(var(--color-surface))',
    color: 'rgb(var(--color-ink))',
    border: '1px solid rgb(var(--color-line-strong))',
    borderRadius: '8px',
    boxShadow: '0 16px 32px -16px rgb(var(--color-ink) / 0.35)',
    fontSize: '0.875rem',
  },
  success: { iconTheme: { primary: 'rgb(var(--color-success))', secondary: 'rgb(var(--color-surface))' } },
  error: { iconTheme: { primary: 'rgb(var(--color-danger))', secondary: 'rgb(var(--color-surface))' } },
};

/**
 * Proveedores globales de la aplicación (errores, alertas toast, React Query)
 * */
export const AppProviders = ({ children }) => {
  // Mantiene sincronizados el tema del sistema, las pestañas y theme-color aunque ningún componente muestre el selector.
  useTheme();

  return (
    // El CSS global de prefers-reduced-motion no frena a framer-motion (anima por JS): `reducedMotion="user"`
    // desactiva las animaciones de transformación cuando el sistema lo pide.
    <MotionConfig reducedMotion="user">
      <AppErrorBoundary>
        <QueryClientProvider client={queryClient}>
          {children}
          <Toaster position="bottom-center" toastOptions={TOAST_OPTIONS} />
        </QueryClientProvider>
      </AppErrorBoundary>
    </MotionConfig>
  );
};