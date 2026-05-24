import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');

  return {
    plugins: [react()],
    server: {
      host: '0.0.0.0',
      port: 5174,
      strictPort: true,
      watch: {
        usePolling: true,
      },
      hmr: {
        host: 'localhost',
        port: 5174,
      },
    },
    preview: {
      host: '0.0.0.0',
      port: 4174,
      strictPort: true,
    },
    build: {
      rollupOptions: {
        output: {
          manualChunks: {
            'vendor-react': ['react', 'react-dom', 'react-router-dom'],
            // librerías pesadas aparte
            'vendor-utils': ['framer-motion', '@tanstack/react-query', 'axios'],
            'vendor-ui': ['lucide-react', 'react-hot-toast']
          }
        }
      }
    }
  };
});