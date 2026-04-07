import { useContext } from 'react';
import { UserRankingContext } from '../context/KudosRankingProvider.jsx';

export const useUserRankingContext = () => {
  const context = useContext(UserRankingContext);

  if (!context) {
    throw new Error('useUserRankingContext debe utilizarse dentro de UserRankingProvider.');
  }

  return context;
};