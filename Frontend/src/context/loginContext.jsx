import { createContext, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useLoginMutation } from '../hooks/useLoginMutation';

export const LoginContext = createContext(null);

export const LoginProvider = ({ children }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const location = useLocation();
  const navigate = useNavigate();
  const loginMutation = useLoginMutation();

  const fromPath = location.state?.from?.pathname ?? '/profile';

  const updateEmail = (value) => setEmail(value);
  const updatePassword = (value) => setPassword(value);

  const submitLogin = async (event) => {
    event.preventDefault();

    try {
      const result = await loginMutation.mutateAsync({ email, password });
      if (result?.token) {
        navigate(fromPath, { replace: true });
      }
    } catch {
      // El mutation ya maneja los errores de API.
    }
  };

  const value = useMemo(
    () => ({
      email,
      password,
      isSubmitting: loginMutation.isPending,
      updateEmail,
      updatePassword,
      submitLogin,
    }),
    [email, password, loginMutation.isPending],
  );

  return <LoginContext.Provider value={value}>{children}</LoginContext.Provider>;
};
