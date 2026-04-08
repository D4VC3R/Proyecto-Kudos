import { Outlet } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';

export const AppLayout = () => {
  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col">
      {/* El Navbar es global. Si necesita saber si el usuario es admin para
        mostrar un enlace, consumirá el Zustand store internamente.
      */}
      <header className="w-full border-b border-slate-800 bg-slate-900">
        <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
          <div className="font-bold text-xl">Kudos App</div>
          <nav>
            {/* Enlaces de navegación irían aquí */}
          </nav>
        </div>
      </header>

      {/* Contenedor principal donde se inyectan las páginas de las rutas */}
      <main className="mx-auto w-full max-w-6xl flex-grow px-4 py-8">
        <Outlet />
      </main>

      {/* Proveedor global de notificaciones */}
      <Toaster position="bottom-right" reverseOrder={false} />
    </div>
  );
};