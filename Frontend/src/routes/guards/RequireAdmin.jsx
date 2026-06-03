import { Navigate, Outlet } from 'react-router-dom';
import { selectIsAdmin, useSessionStore } from '../../store/useSessionStore';

/**
 * Componente de guardia que protege las rutas que requieren permisos de administrador.
 * Si el usuario no es administrador, se redirige a la página de "Forbidden".
 * */
const RequireAdmin = () => {
  const isAdmin = useSessionStore(selectIsAdmin);

  return isAdmin ? <Outlet /> : <Navigate replace to="/forbidden" />;
};

export default RequireAdmin;
