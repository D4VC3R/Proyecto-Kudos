import { AppProviders } from './app/AppProviders.jsx';
import { AppRoutes } from './routes/routes.jsx';
import {AppNav} from "./components/navigation/AppNav.jsx";

const App = () => {
  return (
    <AppProviders>
      <AppRoutes />
    </AppProviders>
  );
};

export default App;
