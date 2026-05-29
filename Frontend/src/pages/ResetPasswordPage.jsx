import React, {useState} from 'react';
import {zodResolver} from '@hookform/resolvers/zod';
import {Lock, ShieldCheck} from 'lucide-react';
import {resetPasswordSchema} from '../lib/schemas';
// Componentes
import InputField from '../components/common/InputField';
import Button from '../components/common/Buttons/Button.jsx';
import ScaleFadeIn from '../components/animations/ScaleFadeIn';
import FeedbackState from '../components/common/FeedbackState';
// Hooks
import {useParams, useSearchParams, useNavigate} from 'react-router-dom';
import {useResetPassword} from '../hooks/auth/useAuthMutations';
import {useForm} from 'react-hook-form';

const ResetPasswordPage = () => {
  const {token} = useParams();
  const [searchParams] = useSearchParams();
  const email = searchParams.get('email');

  const navigate = useNavigate();
  const [isSuccess, setIsSuccess] = useState(false);
  const {mutate: resetPassword, isPending} = useResetPassword();

  const {register, handleSubmit, formState: {errors}} = useForm({
    resolver: zodResolver(resetPasswordSchema)
  });

  const onSubmit = (data) => {
    const payload = {...data, token, email};

    resetPassword(payload, {
      onSuccess: () => setIsSuccess(true)
    });
  };

  if (!token || !email) {
    return (
      <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
        <ScaleFadeIn className="w-full max-w-md rounded-3xl bg-surface p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">
          <FeedbackState
            icon={Lock}
            iconColorClass="bg-red-100 text-red-500"
            title="Enlace inválido"
            description="Faltan parámetros de seguridad en la URL. Por favor, vuelve a solicitar el restablecimiento."
            actionText="Solicitar nuevo enlace"
            onAction={() => navigate('/forgot-password')}
          />
        </ScaleFadeIn>
      </div>
    );
  }

  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <ScaleFadeIn className="w-full max-w-md rounded-3xl bg-surface p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">

        {isSuccess ? (
          <FeedbackState
            icon={ShieldCheck}
            iconColorClass="bg-green-100 text-green-500"
            title="¡Contraseña actualizada!"
            description="Tu contraseña se ha cambiado correctamente. Todas tus sesiones anteriores han sido cerradas por seguridad."
            actionText="Iniciar Sesión"
            actionColorClass="bg-primary text-text-btn"
            onAction={() => navigate('/login')}
          />
        ) : (
          <>
            <div className="mb-8 text-center flex flex-col items-center">
              <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-primary">
                <Lock size={32}/>
              </div>
              <h1 className="text-2xl font-black text-text-highlight">Nueva contraseña</h1>
              <p className="mt-2 text-sm text-text-normal">
                Elige una contraseña segura para la cuenta asociada a <span className="font-bold">{email}</span>.
              </p>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
              <InputField
                label="Nueva contraseña"
                type="password"
                icon={Lock}
                placeholder="••••••••"
                registration={register('password')}
                error={errors.password}
                disabled={isPending}
              />

              <InputField
                label="Confirmar contraseña"
                type="password"
                icon={Lock}
                placeholder="••••••••"
                registration={register('password_confirmation')}
                error={errors.password_confirmation}
                disabled={isPending}
              />

              <Button
                type="submit"
                isLoading={isPending}
                isFullWidth
                variant="solid"
                color="primary"
              >
                Actualizar contraseña
              </Button>
            </form>
          </>
        )}
      </ScaleFadeIn>
    </div>
  );
};

export default ResetPasswordPage;