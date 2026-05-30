import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore';

const RequireGuest = () => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const location = useLocation();

  if (isAuthenticated) {
    // Si venía rebotado de una ruta protegida, lo devolvemos allí, si no, al lobby.
    const destination = location.state?.from?.pathname || '/';
    return <Navigate replace to={destination} />;
  }

  return <Outlet />;
};

export default RequireGuest;