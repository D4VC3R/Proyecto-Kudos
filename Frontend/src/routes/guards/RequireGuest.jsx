import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore';

/**
 * Componente de guardia que protege las rutas que solo deben ser accesibles para usuarios no autenticados (invitados).
 * Si el usuario está autenticado, se redirige a la página de inicio o a la página desde la que intentó acceder.
 * Útil para páginas como login o registro, donde no tiene sentido que un usuario autenticado acceda.
 * */
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