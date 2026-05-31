import AppRoutes from './routes/routes.jsx';
import MainHeader from "./components/layout/MainHeader.jsx";
import Footer from "./components/layout/Footer.jsx";
import ScrollToTopButton from "./components/ui/ScrollToTopButton.jsx";
import DailyRewardChecker from "./components/ui/DailyRewardChecker.jsx";

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
