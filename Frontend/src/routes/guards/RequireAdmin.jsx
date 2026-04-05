import { Navigate, Outlet } from 'react-router-dom';
import { selectIsAdmin, useSessionStore } from '../../store/useSessionStore';

export const RequireAdmin = () => {
  const isAdmin = useSessionStore(selectIsAdmin);

  return isAdmin ? <Outlet /> : <Navigate replace to="/forbidden" />;
};
