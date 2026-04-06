import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useLoginMutation } from '../../hooks/useLoginMutation';

export const LoginApiForm = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const location = useLocation();
  const navigate = useNavigate();
  const loginMutation = useLoginMutation();

  const fromPath = location.state?.from?.pathname ?? '/profile';

  const handleSubmit = async (event) => {
    event.preventDefault();

    try {
      const result = await loginMutation.mutateAsync({ email, password });

      if (result?.token) {
        navigate(fromPath, { replace: true });
      }
    } catch (error) {
      // El mutation ya maneja los errores de API (toasts).
    }
  };

  return (
    <form className="space-y-3" onSubmit={handleSubmit}>
      <label className="block space-y-1 text-sm">
        <span>Email</span>
        <input
          className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          onChange={(event) => setEmail(event.target.value)}
          required
          type="email"
          value={email}
        />
      </label>

      <label className="block space-y-1 text-sm">
        <span>Contrasena</span>
        <input
          className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          onChange={(event) => setPassword(event.target.value)}
          required
          type="password"
          value={password}
        />
      </label>

      <button
        className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70"
        disabled={loginMutation.isPending}
        type="submit"
      >
        {loginMutation.isPending ? 'Entrando...' : 'Entrar'}
      </button>
    </form>
  );
};