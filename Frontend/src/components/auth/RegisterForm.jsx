import React from 'react';
import {Link, useLocation, useNavigate} from 'react-router-dom';
import {zodResolver} from '@hookform/resolvers/zod';
import {Mail, Lock, User} from 'lucide-react';
import {registerSchema} from '../../lib/schemas';

import InputField from '../ui/InputField.jsx';
import Button from '../ui/Button.jsx';

import {useForm} from 'react-hook-form';
import {requiresEmailVerification, useRegister} from '../../hooks/auth/useAuthMutations';

const RegisterForm = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const {mutate: registerUser, isPending} = useRegister();

  const {register, handleSubmit, formState: {errors}} = useForm({
    resolver: zodResolver(registerSchema),
    defaultValues: {accept_privacy: false, age_confirmation: false}
  });

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
      <InputField
        label="Nombre de Usuario"
        type="text"
        icon={User}
        placeholder=""
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

      <div>
        <label className="flex items-start gap-2 text-sm text-text-normal cursor-pointer">
          <input type="checkbox" {...register('accept_privacy')} disabled={isPending} className="mt-1 accent-blue-600"/>
          <span>
            He leído y acepto la{' '}
            <Link to="/privacidad" target="_blank" rel="noopener noreferrer" className="font-bold text-primary hover:underline">
              política de privacidad
            </Link>
          </span>
        </label>
        {errors.accept_privacy && <span className="mt-1 text-xs text-red-500">{errors.accept_privacy.message}</span>}
      </div>

      <div>
        <label className="flex items-start gap-2 text-sm text-text-normal cursor-pointer">
          <input type="checkbox" {...register('age_confirmation')} disabled={isPending} className="mt-1 accent-blue-600"/>
          <span>Tengo 14 años o más</span>
        </label>
        {errors.age_confirmation && <span className="mt-1 text-xs text-red-500">{errors.age_confirmation.message}</span>}
      </div>

      <Button
        type="submit"
        isLoading={isPending}
        isFullWidth
        variant="solid"
        color="primary"
      >
        Registrarse
      </Button>
    </form>
  );
};

export default RegisterForm;
