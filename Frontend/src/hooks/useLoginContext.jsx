import { useContext } from 'react';
import { LoginContext } from '../context/LoginProvider.jsx';

export const useLoginContext = () => {
  const context = useContext(LoginContext);

  if (!context) {
    throw new Error('useLoginContext debe utilizarse dentro de LoginProvider.');
  }

  return context;
};
