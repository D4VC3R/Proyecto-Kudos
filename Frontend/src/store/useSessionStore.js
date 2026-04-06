import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

const STORAGE_KEY = 'kudos_session';


export const selectToken = (state) => state.token;
export const selectUser = (state) => state.user;
export const selectHasRoleAdmin = (state) => {
  const role = state.user?.role;
  return role === 'admin' || Boolean(state.user?.is_admin);
};
export const selectIsAuthenticated = (state) => Boolean(state.token);
export const selectIsVerified = (state) => Boolean(state.user?.email_verified_at || state.user?.is_verified);
export const selectIsBanned = (state) => Boolean(state.user?.is_banned);
export const selectIsAdmin = (state) => selectHasRoleAdmin(state);

export const useSessionStore = create(
  persist(
    (set) => ({
      token: null,
      user: null,
      setSession: ({ token, user }) => {
        set({ token: token ?? null, user: user ?? null });
      },
      clearSession: () => {
        set({ token: null, user: null });
      },
    }),
    {
      name: STORAGE_KEY,
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({ token: state.token, user: state.user }),
    },
  ),
);
