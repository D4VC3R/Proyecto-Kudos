import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore';
import { getReturnPath } from '../../lib/authRedirect.js';

/**
 * Componente de guardia que protege las rutas que solo deben ser accesibles para usuarios no autenticados (invitados).
 * Si el usuario está autenticado, se redirige a la página de inicio o a la página desde la que intentó acceder.
 * Útil para páginas como login o registro, donde no tiene sentido que un usuario autenticado acceda.
 * */
const RequireGuest = () => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const location = useLocation();

  if (isAuthenticated) {
    // Lo devolvemos a la página de origen (ruta protegida o enlace de la cabecera); si no hay, al lobby.
    const destination = getReturnPath(location.state?.from);
    return <Navigate replace to={destination} />;
  }

  return <Outlet />;
};

export default RequireGuest;