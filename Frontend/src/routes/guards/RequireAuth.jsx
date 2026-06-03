import {Navigate, Outlet, useLocation} from 'react-router-dom';
import {selectIsAuthenticated, useSessionStore} from '../../store/useSessionStore';

/**
 * Componente de guardia que protege las rutas que requieren autenticación.
 * Si el usuario no está autenticado, se redirige a la página de login.
 * Además, se guarda la ubicación actual para redirigir al usuario de vuelta después de iniciar sesión.
 * */
const RequireAuth = () => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const location = useLocation();

  return isAuthenticated ? <Outlet/> : <Navigate replace state={{from: location}} to="/login"/>;
};

export default RequireAuth;