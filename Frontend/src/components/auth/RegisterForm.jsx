import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Mail, Lock, User } from 'lucide-react';
import { useRegister } from '../../hooks/auth/useAuthMutations';
import { useNavigate } from 'react-router-dom';
import { registerSchema } from '../../lib/schemas/authSchemas';

export const RegisterForm = () => {
  const navigate = useNavigate();
  const { mutate: registerUser, isPending } = useRegister();
  
  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(registerSchema)
  });

  const onSubmit = (data) => {
    registerUser(data, {
      onSuccess: () => {
        navigate('/verify-email', { replace: true, state: { registered: true } });
      }
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4">
      <div>
        <label className="mb-2 block text-sm font-bold text-slate-700">Nombre de Usuario</label>
        <div className="relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <User size={18} />
          </div>
          <input
            type="text"
            {...register('name')}
            disabled={isPending}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
            placeholder="Tu nombre ninja"
          />
        </div>
        {errors.name && <span className="mt-1 text-xs text-red-500">{errors.name.message}</span>}
      </div>

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
        <label className="mb-2 block text-sm font-bold text-slate-700">Contraseña</label>
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

      <div>
        <label className="mb-2 block text-sm font-bold text-slate-700">Confirmar Contraseña</label>
        <div className="relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <Lock size={18} />
          </div>
          <input
            type="password"
            {...register('password_confirmation')}
            disabled={isPending}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
            placeholder="••••••••"
          />
        </div>
        {errors.password_confirmation && <span className="mt-1 text-xs text-red-500">{errors.password_confirmation.message}</span>}
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
      >
        {isPending ? <Loader2 size={20} className="animate-spin" /> : 'Registrarse y jugar'}
      </button>
    </form>
  );
};
