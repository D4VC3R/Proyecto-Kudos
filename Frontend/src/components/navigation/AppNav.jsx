import { Link, NavLink } from 'react-router-dom';

export const AppNav = () => {

  const activeLinkClass = 'bg-slate-800 text-white';
  const { token, user, isAdmin, isLoggingOut, logout } = useAppLayoutContext();

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
                disabled={}
                onClick={() => logoutMutation.mutate()}
                disabled={isLoggingOut}
                {logoutMutation.isPending ? 'Cerrando...' : 'Cerrar sesion'}
                type="button"
              >
                {isLoggingOut ? 'Cerrando...' : 'Cerrar sesion'}
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