import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Mail, Lock } from 'lucide-react';
import { useLogin } from '../../hooks/auth/useAuthMutations';
import { useNavigate, useLocation } from 'react-router-dom';
import { loginSchema } from '../../lib/schemas';
import { InputField } from '../common/InputField';

export const LoginForm = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { mutate: login, isPending } = useLogin();
  
  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(loginSchema)
  });

  const onSubmit = (data) => {
    login(data, {
      onSuccess: () => {
        const destination = location.state?.from?.pathname || '/';
        navigate(destination, { replace: true });
      }
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
      <InputField
        label="Email"
        type="email"
        icon={Mail}
        placeholder="tu@email.com"
        registration={register('email')}
        error={errors.email}
        disabled={isPending}
      />

      <InputField
        label="Contraseña"
        type="password"
        icon={Lock}
        placeholder="••••••••"
        registration={register('password')}
        error={errors.password}
        disabled={isPending}
        labelEnd={
          <a href="#" className="text-xs font-bold text-blue-600 hover:text-blue-700">¿Olvidaste tu contraseña?</a>
        }
      />

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
