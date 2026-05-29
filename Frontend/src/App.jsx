import AppRoutes from './routes/routes.jsx';
import MainHeader from "./components/layout/MainHeader.jsx";
import ScrollToTopButton from "./components/ui/ScrollToTopButton.jsx";
import DailyRewardChecker from "./components/ui/DailyRewardChecker.jsx";

const App = () => {
  return (
    <div className="bg-background text-text-highlight flex flex-col font-sans">
      <MainHeader />
      <DailyRewardChecker />
      <main className="mx-auto w-full h-fit min-h-lvh max-w-7xl flex-grow px-4 py-8 sm:px-6 lg:px-8">
        <AppRoutes />
      </main>
      <ScrollToTopButton />
    </div>
  );
};

export default App;
