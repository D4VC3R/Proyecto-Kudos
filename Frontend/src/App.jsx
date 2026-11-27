import AppRoutes from './routes/routes.jsx';
import MainHeader from "./components/layout/MainHeader.jsx";
import Footer from "./components/layout/Footer.jsx";
import ScrollToTopButton from "./components/ui/ScrollToTopButton.jsx";
import DailyRewardChecker from "./components/ui/DailyRewardChecker.jsx";
import AddToListModal from "./components/lists/AddToListModal.jsx";
/**
 * Bienvenido a Proyecto Kudos, aquí tienes la estructura del proyecto por si necesitas modificar algo:
 * - app: Contiene los proveedores de la aplicación.
 * - components: Componentes reutilizables, contienen poca o ninguna lógica.
 * - core: Configuración de Axios, React-Query y componente fallback para errores fatales.
 * - hooks: Hooks personalizados para manejar la lógica de negocio, queries, mutaciones y acciones. Separados por tipo (useX) y por página (useXPage).
 * - pages: Componentes de página, que representan las vistas principales de la aplicación.
 * - routes: Configuración de rutas y componentes de guardia para proteger rutas según el estado del usuario.
 * - store: Zustand para manejar el estado global de la sesión del usuario.
 * */
const App = () => {
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

      <main id="contenido" tabIndex={-1} className="mx-auto w-full max-w-7xl flex-grow scroll-mt-20 px-4 py-8 outline-none sm:px-6 lg:px-8">
        <AppRoutes />
      </main>

      <Footer />
      <ScrollToTopButton />
    </div>
  );
};

export default App;
