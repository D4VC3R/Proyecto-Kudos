import { Navigate, Outlet } from 'react-router-dom';
import { selectIsBanned, selectIsVerified, useSessionStore } from '../../store/useSessionStore';

export const RequireVerified = () => {
  const isVerified = useSessionStore(selectIsVerified);
  const isBanned = useSessionStore(selectIsBanned);

  const shouldBlock = isBanned || !isVerified;
  const content = shouldBlock ? <Navigate replace to="/forbidden" /> : <Outlet />;

  return content;
};
