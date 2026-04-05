import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore';

export const RequireAuth = () => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const location = useLocation();

  const content = isAuthenticated ? <Outlet /> : <Navigate replace state={{ from: location }} to="/login" />;

  return content;
};
