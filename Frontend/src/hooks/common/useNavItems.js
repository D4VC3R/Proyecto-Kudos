import { Grid, Trophy, Bookmark, ListPlus, ShieldPlus } from 'lucide-react';
import { selectIsAdmin, selectIsAuthenticated, selectIsVerified, useSessionStore } from '../../store/useSessionStore.js';

const navItems = [
  { name: 'Categorías', path: '/', icon: Grid },
  { name: 'Ranking', path: '/ranking', icon: Trophy },
  { name: 'Wishlist', path: '/wishlist', icon: Bookmark, requiresVerified: true },
  { name: 'Mis listas', path: '/mis-listas', icon: ListPlus, requiresVerified: true },
  { name: 'Administración', path: '/admin', icon: ShieldPlus, requiresAdmin: true }
];

/** Enlaces de navegación que el usuario actual puede ver (los de administración, solo si es administrador). */
export const useNavItems = () => {
  const isAdmin = useSessionStore(selectIsAdmin);
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const isVerified = useSessionStore(selectIsVerified);

  return navItems.filter(
    (item) => (!item.requiresAdmin || isAdmin) && (!item.requiresVerified || (isAuthenticated && isVerified))
  );
};
