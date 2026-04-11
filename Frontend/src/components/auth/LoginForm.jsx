import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Loader2, Mail, Lock } from 'lucide-react';
import { useLogin } from '../../hooks/auth/useAuthMutations';
import { useNavigate } from 'react-router-dom';

const loginSchema = z.object({
  email: z.string().min(1, 'El email es requerido').email('Email no válido'),
  password: z.string().min(1, 'La contraseña es requerida'),
});

export const LoginForm = () => {
  const navigate = useNavigate();
  const { mutate: login, isPending } = useLogin();
  
  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(loginSchema)
  });

  const onSubmit = (data) => {
    login(data, {
      onSuccess: () => navigate('/') // Redirect to home on success
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
      <div>
        <label className="mb-2 block text-sm font-bold text-slate-700">Email</label>
        <div className="relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <Mail size={18} />
          </div>
          <input
            type="email"
            {...register('email')}
            disabled={isPending}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
            placeholder="tu@email.com"
          />
        </div>
        {errors.email && <span className="mt-1 text-xs text-red-500">{errors.email.message}</span>}
      </div>

      <div>
        <div className="mb-2 flex items-center justify-between">
          <label className="text-sm font-bold text-slate-700">Contraseña</label>
          <a href="#" className="text-xs font-bold text-blue-600 hover:text-blue-700">¿Olvidaste tu contraseña?</a>
        </div>
        <div className="relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <Lock size={18} />
          </div>
          <input
            type="password"
            {...register('password')}
            disabled={isPending}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
            placeholder="••••••••"
          />
        </div>
        {errors.password && <span className="mt-1 text-xs text-red-500">{errors.password.message}</span>}
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
      >
        {isPending ? <Loader2 size={20} className="animate-spin" /> : 'Iniciar Sesión'}
      </button>
    </form>
  );
};

