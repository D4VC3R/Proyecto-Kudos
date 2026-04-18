import { Outlet, Link } from 'react-router-dom';
import { useSessionStore } from '../../store/useSessionStore';
import { HeaderLogin } from '../../components/layout/HeaderLogin';
import { UserInfo } from '../../components/layout/UserInfo';
import { NavBar } from '../../components/layout/NavBar';
import { LogoutButton } from '../../components/auth/LogoutButton';
import { Gamepad2 } from 'lucide-react';

export const AppLayout = () => {
  const isAuthenticated = useSessionStore((state) => !!state.token);

  return (
    <div className="min-h-9 bg-slate-50 text-slate-900 flex flex-col font-sans">
      {/* El Navbar es global. Si necesita saber si el usuario es admin para
        mostrar un enlace, consumirá el Zustand store internamente.
      */}
      <header className="sticky top-0 z-50 w-full border-b border-slate-200 bg-white shadow-sm">
        <div className="mx-auto flex h-[72px] w-full max-w-7xl items-center px-4 relative justify-between">

          <Link to="/" className="flex shrink-0 items-center justify-start gap-2 transition-transform hover:scale-105">
            <div className="flex h-9 w-9 md:h-10 md:w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md">
              <Gamepad2 size={20} className="md:h-6 md:w-6" />
            </div>
            <div className="font-black text-xl md:text-2xl text-slate-900 tracking-tight whitespace-nowrap">
              Kudos<span className="text-blue-600">App</span>
            </div>
          </Link>
          
          <div className="order-last md:order-2 md:flex-1 md:flex md:justify-center">
            <NavBar />
          </div>

          <div className="flex shrink-0 items-center justify-end gap-2 order-2 md:order-3 mx-2 md:mx-0 min-w-0">
            {isAuthenticated ? (
              <>
                <UserInfo />
                <LogoutButton />
              </>
            ) : (
              <HeaderLogin />
            )}
          </div>

        </div>
      </header>

      {/* Contenedor principal donde se inyectan las páginas de las rutas */}
      <main className="mx-auto w-full h-full max-w-7xl flex-grow px-4 py-8 sm:px-6 lg:px-8">
        <Outlet />
      </main>
    </div>
  );
};