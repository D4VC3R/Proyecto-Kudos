import {Navigate, Outlet, useLocation} from 'react-router-dom';
import {selectIsAuthenticated, useSessionStore} from '../../store/useSessionStore';

const RequireAuth = () => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const location = useLocation();

  return isAuthenticated ? <Outlet/> : <Navigate replace state={{from: location}} to="/login"/>;
};

export default RequireAuth;