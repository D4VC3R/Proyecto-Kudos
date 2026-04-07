import { useContext } from 'react';
import { ProfileContext } from '../context/ProfileProvider.jsx';

export const useProfileContext = () => {
  const context = useContext(ProfileContext);

  if (!context) {
    throw new Error('useProfileContext debe utilizarse dentro de ProfileProvider.');
  }

  return context;
};
