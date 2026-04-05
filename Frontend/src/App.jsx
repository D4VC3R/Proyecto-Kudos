import { AppProviders } from './app/AppProviders.jsx';
import { AppRoutes } from './routes/routes.jsx';

const App = () => {
  return (
    <AppProviders>
      <AppRoutes />
    </AppProviders>
  );
};

export default App;
