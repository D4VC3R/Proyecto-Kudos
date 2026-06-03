import AppRoutes from './routes/routes.jsx';
import MainHeader from "./components/layout/MainHeader.jsx";
import Footer from "./components/layout/Footer.jsx";
import ScrollToTopButton from "./components/ui/ScrollToTopButton.jsx";
import DailyRewardChecker from "./components/ui/DailyRewardChecker.jsx";
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
      <MainHeader />
      <DailyRewardChecker />

      <main className="mx-auto w-full max-w-7xl flex-grow px-4 py-8 sm:px-6 lg:px-8">
        <AppRoutes />
      </main>

      <Footer />
      <ScrollToTopButton />
    </div>
  );
};

export default App;
