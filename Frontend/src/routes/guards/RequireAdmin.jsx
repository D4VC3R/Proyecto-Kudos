import { Navigate, Outlet } from 'react-router-dom';
import { selectIsAdmin, useSessionStore } from '../../store/useSessionStore';

const RequireAdmin = () => {
  const isAdmin = useSessionStore(selectIsAdmin);

  return isAdmin ? <Outlet /> : <Navigate replace to="/forbidden" />;
};

export default RequireAdmin;
