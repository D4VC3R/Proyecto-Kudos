import React from 'react';
import { LogOut } from 'lucide-react';
import { useLogout } from '../../hooks/auth/useAuthMutations';
import  Button  from '../ui/Button.jsx';

const LogoutButton = () => {
  const logoutMutation = useLogout();

  const handleLogout = () => {
    logoutMutation.mutate();
  };

  return (
    <Button
      onClick={handleLogout}
      disabled={logoutMutation.isPending}
      variant="ghost"
      color="danger"
      size="iconMd"
      radius="full"
      aria-label="Cerrar sesión"
      title="Cerrar sesión"
      icon={LogOut}
    />
  );
};

export default LogoutButton;
