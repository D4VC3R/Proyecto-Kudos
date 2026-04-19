import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Mail, Lock, User } from 'lucide-react';
import { useRegister } from '../../hooks/auth/useAuthMutations';
import { useNavigate } from 'react-router-dom';
import { registerSchema } from '../../lib/schemas';
import { InputField } from '../common/InputField';

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
      <InputField
        label="Nombre de Usuario"
        type="text"
        icon={User}
        placeholder="Tu nombre ninja"
        registration={register('name')}
        error={errors.name}
        disabled={isPending}
      />

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
      />

      <InputField
        label="Confirmar Contraseña"
        type="password"
        icon={Lock}
        placeholder="••••••••"
        registration={register('password_confirmation')}
        error={errors.password_confirmation}
        disabled={isPending}
      />

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
