import React from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { loginSchema } from '../../lib/schemas';
import {  Mail, Lock } from 'lucide-react';

import Input from '../ui/Input.jsx';
import Button from '../ui/Button.jsx';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useLogin } from '../../hooks/auth/useAuthMutations';
import { getReturnPath } from '../../lib/authRedirect.js';
import { ONBOARDING_PATH } from '../../lib/onboarding.js';
import { useSessionStore } from '../../store/useSessionStore';

const LoginForm = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { mutate: login, isPending } = useLogin();

  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(loginSchema)
  });

  const onSubmit = (data) => {
    login(data, {
      onSuccess: () => {
        // Cuenta verificada sin intereses: primero el onboarding, que recuerda la página de origen.
        if (useSessionStore.getState().onboardingPending) {
          navigate(ONBOARDING_PATH, { replace: true, state: { from: location.state?.from } });
          return;
        }
        const destination = getReturnPath(location.state?.from);
        navigate(destination, { replace: true });
      }
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
      <Input
        label="Email"
        type="email"
        icon={Mail}
        placeholder="tu@email.com"
        registration={register('email')}
        error={errors.email}
        disabled={isPending}
      />

      <Input
        label="Contraseña"
        type="password"
        icon={Lock}
        placeholder="••••••••"
        registration={register('password')}
        error={errors.password}
        disabled={isPending}
        labelEnd={
          <Link
            to="/forgot-password"
            className="rounded-sm text-caption font-semibold text-accent underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
          >
            ¿Olvidaste tu contraseña?
          </Link>
        }
      />

      <Button
        type="submit"
        isLoading={isPending}
        isFullWidth
      >
        Iniciar sesión
      </Button>
    </form>
  );
};

export default LoginForm;
