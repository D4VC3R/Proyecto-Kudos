import {Navigate, Outlet} from 'react-router-dom';
import {selectIsBanned, selectIsVerified, useSessionStore} from '../../store/useSessionStore';

/**
 * Componente de guardia que protege las rutas que requieren que el usuario esté verificado y no esté baneado.
 * Si el usuario no está verificado o está baneado, se redirige a la página de "Forbidden".
 * */
const RequireVerified = () => {
  const isVerified = useSessionStore(selectIsVerified);
  const isBanned = useSessionStore(selectIsBanned);

  const shouldBlock = isBanned || !isVerified;
  return shouldBlock ? <Navigate replace to="/forbidden"/> : <Outlet/>;
};

export default RequireVerified;