import {AppRoutes} from './routes/routes.jsx';
import {MainHeader} from "./components/common/MainHeader.jsx";
import {ScrollToTop} from "./components/common/ScrollToTop.jsx";
import {useState} from "react";
import {useDailyReward} from "./hooks/auth/useDailyReward.js";
import {DailyRewardModal} from "./components/common/DailyRewardModal.jsx";

const App = () => {
  const [rewardData, setRewardData] = useState(null);
  useDailyReward((data) => {setRewardData(data);});

  return (
    <div className="min-h-9 bg-slate-50 text-slate-900 flex flex-col font-sans">
      <MainHeader />
      <main className="mx-auto w-full h-fit min-h-lvh max-w-7xl flex-grow px-4 py-8 sm:px-6 lg:px-8">
        <AppRoutes />
      </main>
      <ScrollToTop />
      <DailyRewardModal
        isOpen={!!rewardData}
        onClose={() => setRewardData(null)}
        data={rewardData}
      />
    </div>
  );
};

export default App;
