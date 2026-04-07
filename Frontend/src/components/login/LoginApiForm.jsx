import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

export const LoginApiForm = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const location = useLocation();
  const navigate = useNavigate();
  const {  } = useLoginContext();


  return (
    <form className="space-y-3" onSubmit={handleSubmit}>
      <label className="block space-y-1 text-sm">
      // El mutation ya maneja los errores de API (toasts).
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
        disabled={isLoggingIn}
        type="submit"
      >
        {loginMutation.isPending ? 'Entrando...' : 'Entrar'}
        {isLoggingIn ? 'Entrando...' : 'Entrar'}
      </button>
    </form>
  );
};