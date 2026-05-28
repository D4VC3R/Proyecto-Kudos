import {AppRoutes} from './routes/routes.jsx';
import {MainHeader} from "./components/common/MainHeader.jsx";
import {ScrollToTop} from "./components/common/ScrollToTop.jsx";
import {DailyRewardChecker} from "./components/common/DailyRewardChecker.jsx";

const App = () => {
  return (
    <div className="bg-background text-text-highlight flex flex-col font-sans">
      <MainHeader />
      <DailyRewardChecker />
      <main className="mx-auto w-full h-fit min-h-lvh max-w-7xl flex-grow px-4 py-8 sm:px-6 lg:px-8">
        <AppRoutes />
      </main>
      <ScrollToTop />
    </div>
  );
};

export default App;
