import { useLocation } from 'react-router-dom';
import AppRoutes from './routes/routes.jsx';
import MainHeader from "./components/layout/MainHeader.jsx";
import Footer from "./components/layout/Footer.jsx";
import ScrollToTopButton from "./components/ui/ScrollToTopButton.jsx";
import DailyRewardChecker from "./components/ui/DailyRewardChecker.jsx";
import AddToListModal from "./components/lists/AddToListModal.jsx";

// Ancho del contenido según la página: la administración usa tablas anchas (panel), los textos legales se leen
// mejor con líneas cortas (lectura) y el resto va al ancho de la maqueta (contenido).
const pageWidthFor = (pathname) => {
  if (pathname === '/admin' || pathname.startsWith('/admin/')) return 'max-w-panel';
  if (pathname === '/privacidad' || pathname === '/aviso-legal') return 'max-w-reading';
  return 'max-w-content';
};

/**
 * Estructura del proyecto, por si necesitas modificar algo:
 * - app: Contiene los proveedores de la aplicación.
 * - components: Componentes reutilizables, contienen poca o ninguna lógica.
 * - core: Configuración de Axios, React-Query y componente fallback para errores fatales.
 * - hooks: Hooks personalizados para manejar la lógica de negocio, queries, mutaciones y acciones. Separados por tipo (useX) y por página (useXPage).
 * - pages: Componentes de página, que representan las vistas principales de la aplicación.
 * - routes: Configuración de rutas y componentes de guardia para proteger rutas según el estado del usuario.
 * - store: Zustand para manejar el estado global de la sesión del usuario.
 * */
const App = () => {
  const { pathname } = useLocation();

  return (
    <div className="bg-background text-text-highlight flex flex-col font-sans min-h-[100dvh]">
      {/* Primer elemento enfocable: con teclado, salta la cabecera y lleva al contenido de la página. */}
      <a
        href="#contenido"
        className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:font-bold focus:text-text-btn focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-primary"
      >
        Saltar al contenido
      </a>
      <MainHeader />
      <DailyRewardChecker />
      <AddToListModal />

      <main id="contenido" tabIndex={-1} className={`mx-auto w-full flex-grow scroll-mt-16 px-4 py-8 outline-none md:px-6 ${pageWidthFor(pathname)}`}>
        <AppRoutes />
      </main>

      <Footer />
      <ScrollToTopButton />
    </div>
  );
};

export default App;
