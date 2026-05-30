import {Navigate, Outlet} from 'react-router-dom';
import {selectIsBanned, selectIsVerified, useSessionStore} from '../../store/useSessionStore';

const RequireVerified = () => {
  const isVerified = useSessionStore(selectIsVerified);
  const isBanned = useSessionStore(selectIsBanned);

  const shouldBlock = isBanned || !isVerified;
  return shouldBlock ? <Navigate replace to="/forbidden"/> : <Outlet/>;
};

export default RequireVerified;