import React from 'react';
import {Link, useLocation, useNavigate} from 'react-router-dom';
import {zodResolver} from '@hookform/resolvers/zod';
import {Mail, Lock, User} from 'lucide-react';
import {registerSchema} from '../../lib/schemas';

import Input from '../ui/Input.jsx';
import Checkbox from '../ui/Checkbox.jsx';
import Button from '../ui/Button.jsx';

import {useForm} from 'react-hook-form';
import {requiresEmailVerification, useRegister} from '../../hooks/auth/useAuthMutations';

const RegisterForm = () => {
  const navigate = useNavigate();
  const location = useLocation();

  const {register, handleSubmit, setError, formState: {errors}} = useForm({
    resolver: zodResolver(registerSchema),
    defaultValues: {accept_privacy: false, age_confirmation: false}
  });

  const {mutate: registerUser, isPending} = useRegister({setError});

  const onSubmit = (data) => {
    registerUser(data, {
      onSuccess: (response) => {
        if (requiresEmailVerification(response)) {
          navigate('/verify-email', {replace: true, state: {registered: true}});
        } else {
          navigate('/login', {replace: true, state: location.state});
        }
      }
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4">
      <Input
        label="Nombre de usuario"
        type="text"
        icon={User}
        placeholder=""
        registration={register('name')}
        error={errors.name}
        disabled={isPending}
      />

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
      />

      <Input
        label="Confirmar contraseña"
        type="password"
        icon={Lock}
        placeholder="••••••••"
        registration={register('password_confirmation')}
        error={errors.password_confirmation}
        disabled={isPending}
      />

      <Checkbox
        registration={register('accept_privacy')}
        error={errors.accept_privacy}
        disabled={isPending}
        label={
          <>
            He leído y acepto la{' '}
            <Link
              to="/privacidad"
              target="_blank"
              rel="noopener noreferrer"
              className="rounded-sm font-semibold text-accent underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              política de privacidad
            </Link>
          </>
        }
      />

      <Checkbox
        registration={register('age_confirmation')}
        error={errors.age_confirmation}
        disabled={isPending}
        label="Tengo 14 años o más"
      />

      <Button
        type="submit"
        isLoading={isPending}
        isFullWidth
      >
        Registrarse
      </Button>
    </form>
  );
};

export default RegisterForm;
