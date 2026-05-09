import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import {  Mail, Lock } from 'lucide-react';
import { useLogin } from '../../hooks/auth/useAuthMutations';
import { useNavigate, useLocation } from 'react-router-dom';
import { loginSchema } from '../../lib/schemas';
import { InputField } from '../common/InputField';
import { Button } from '../common/Button';

export const LoginForm = () => {
  const navigate = useNavigate(); // Para redirigir al usuario después del inicio de sesión.
  const location = useLocation(); // Para obtener la ubicación actual y redirigir al usuario a la página que intentaba acceder antes de iniciar sesión.
  const { mutate: login, isPending } = useLogin(); //
  
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

      <Button
        type="submit"
        isLoading={isPending}
        isFullWidth
        variant="solid"
        color="primary"
      >
        Iniciar Sesión
      </Button>
    </form>
  );
};
