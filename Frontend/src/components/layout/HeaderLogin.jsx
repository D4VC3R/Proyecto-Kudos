import React from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { useLogin } from '../../hooks/auth/useAuthMutations';

export const HeaderLogin = () => {
  const { mutate: login, isPending } = useLogin();
  const { register, handleSubmit } = useForm();
  const navigate = useNavigate();

  const onSubmit = (data) => {
    login(data, {
      onSuccess: () => navigate('/')
    });
  };

  return (
    <div className="flex items-center gap-4">
      <form onSubmit={handleSubmit(onSubmit)} className="hidden md:flex items-center gap-2">
        <input
          type="email"
          {...register('email', { required: true })}
          placeholder="Email"
          disabled={isPending}
          className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
        />
        <input
          type="password"
          {...register('password', { required: true })}
          placeholder="Contraseña"
          disabled={isPending}
          className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
        />
        <button
          type="submit"
          disabled={isPending}
          className="flex items-center justify-center rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50"
        >
          {isPending ? <Loader2 size={16} className="animate-spin" /> : 'Entrar'}
        </button>
      </form>
      <div className="flex items-center gap-2 text-sm font-medium">
        <Link to="/login" className="md:hidden text-slate-700 hover:text-slate-900 font-bold whitespace-nowrap px-3">
          Entrar
        </Link>
        <span className="hidden md:block text-slate-400">o</span>
        <Link to="/register" className="text-blue-600 hover:text-blue-700 font-bold whitespace-nowrap">
          Regístrate
        </Link>
      </div>
    </div>
  );
};
