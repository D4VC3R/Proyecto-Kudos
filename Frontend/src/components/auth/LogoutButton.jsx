import React from 'react';
import { LogOut } from 'lucide-react';
import { useLogout } from '../../hooks/auth/useAuthMutations';

export const LogoutButton = () => {
  const logoutMutation = useLogout();

  const handleLogout = () => {
    logoutMutation.mutate();
  };

  return (
    <button
      onClick={handleLogout}
      disabled={logoutMutation.isPending}
      className="flex items-center justify-center p-2.5 text-slate-500 hover:bg-red-50 hover:text-red-600 rounded-full transition-colors border border-transparent hover:border-red-100 outline-none focus:ring-2 focus:ring-red-200 disabled:opacity-50"
      aria-label="Cerrar sesión"
      title="Cerrar sesión"
    >
      <LogOut size={20} strokeWidth={2.5} />
    </button>
  );
};

