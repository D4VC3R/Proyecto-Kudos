import { Link, NavLink } from 'react-router-dom';
import { useLogoutMutation } from '../../hooks/useLogoutMutation';
import { selectIsAdmin, selectToken, selectUser, useSessionStore } from '../../store/useSessionStore';

const baseLinkClass = 'rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white';
const activeLinkClass = 'bg-slate-800 text-white';

export const AppNav = () => {
  const token = useSessionStore(selectToken);
  const user = useSessionStore(selectUser);
  const isAdmin = useSessionStore(selectIsAdmin);
  const logoutMutation = useLogoutMutation();

  const adminNavContent = token && isAdmin ? (
    <NavLink
      className={({ isActive }) => `${baseLinkClass} ${isActive ? activeLinkClass : ''}`}
      to="/admin"
    >
      Admin
    </NavLink>
  ) : null;

  return (
    <header className="sticky top-0 z-10 border-b border-slate-800 bg-slate-950/95 backdrop-blur">
      <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3">
        <Link className="text-base font-semibold text-white" to="/">
          Kudos
        </Link>

        <nav className="flex items-center gap-1">
          <NavLink
            className={({ isActive }) => `${baseLinkClass} ${isActive ? activeLinkClass : ''}`}
            to="/"
          >
            Home
          </NavLink>
          <NavLink
            className={({ isActive }) => `${baseLinkClass} ${isActive ? activeLinkClass : ''}`}
            to="/ranking"
          >
            Ranking
          </NavLink>
          {token && (
            <NavLink
              className={({ isActive }) => `${baseLinkClass} ${isActive ? activeLinkClass : ''}`}
              to="/profile"
            >
              Perfil
            </NavLink>
          )}
          {adminNavContent}
        </nav>

        <div className="flex items-center gap-2">
          {token ? (
            <>
              <span className="hidden text-xs text-slate-400 md:inline">{user?.email ?? 'Sesion iniciada'}</span>
              <button
                className="rounded-md border border-slate-700 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70"
                disabled={logoutMutation.isPending}
                onClick={() => logoutMutation.mutate()}
                type="button"
              >
                {logoutMutation.isPending ? 'Cerrando...' : 'Cerrar sesion'}
              </button>
            </>
          ) : (
            <NavLink className={({ isActive }) => `${baseLinkClass} ${isActive ? activeLinkClass : ''}`} to="/login">
              Iniciar sesion
            </NavLink>
          )}
        </div>
      </div>
    </header>
  );
};