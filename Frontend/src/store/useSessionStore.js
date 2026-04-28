import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

const STORAGE_KEY = 'kudos_session';

// Comprobación segura del rol de usuario
export const selectHasRoleAdmin = (state) => {
  const role = state.user?.role;
  return role === 'admin' || Boolean(state.user?.is_admin);
};
// Selectores de estado del usuario para aplicar los filtros de rutas en routes > guards.
export const selectIsAuthenticated = (state) => Boolean(state.token);
export const selectIsVerified = (state) => Boolean(state.user?.email_verified_at || state.user?.is_verified);
export const selectIsBanned = (state) => Boolean(state.user?.is_banned);
export const selectIsAdmin = (state) => selectHasRoleAdmin(state);

// Hook personalizado para almacenar y gestionar la sesión del usuario, incluyendo el token de autenticación y los datos del usuario.
// Utiliza persistencia con localStorage para mantener la sesión incluso después de recargar la página.
// - create(): Función principal de zustand que inicia el store y devuelve el hook personalizado.
//- persist(): Middleware que intercepta los cambios de estado para guardarlos en el almacenamiento local y recuperarlos al cargar la aplicación.
export const useSessionStore = create(
  persist(
    (set) => ({ // Actualizar el estado. Los componentes dependientes de token o user se vuelven a renderizar si cambia.
      token: null,
      user: null,
      setSession: ({ token, user }) => { // Iniciar sesión.
        set({ token: token ?? null, user: user ?? null });
      },
      clearSession: () => { // Cerrar sesión.
        set({ token: null, user: null });
      },
    }),
    { // Sesión persistente en localStorage con la clave 'kudos_session'.
      name: STORAGE_KEY,
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({ token: state.token, user: state.user }),
    },
  ),
);
