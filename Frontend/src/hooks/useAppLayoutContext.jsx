import { useContext } from 'react';
import { AppLayoutContext } from '../context/AppLayoutProvider.jsx';

export const useAppLayoutContext = () => {
  const context = useContext(AppLayoutContext);

  if (!context) {
    throw new Error('useAppLayoutContext debe utilizarse dentro de AppLayoutProvider.');
  }

  return context;
};
