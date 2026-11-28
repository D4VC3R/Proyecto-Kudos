import React, {useState} from 'react';
import {zodResolver} from '@hookform/resolvers/zod';
import {Lock, ShieldCheck} from 'lucide-react';
import {resetPasswordSchema} from '../../lib/schemas.js';
// Componentes
import Input from '../../components/ui/Input.jsx';
import Button from '../../components/ui/Button.jsx';
import Card from '../../components/ui/Card.jsx';
import FadeUp from '../../components/animations/FadeUp.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
// Hooks
import {useParams, useSearchParams, useNavigate} from 'react-router-dom';
import {useResetPassword} from '../../hooks/auth/useAuthMutations.js';
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
        <FadeUp className="w-full max-w-md">
          <Card className="p-8 sm:p-10">
            <FeedbackState
              icon={Lock}
              iconColorClass="bg-danger/10 text-danger"
              title="Enlace inválido"
              description="Faltan parámetros de seguridad en la URL. Por favor, vuelve a solicitar el restablecimiento."
              actionText="Solicitar nuevo enlace"
              onAction={() => navigate('/forgot-password')}
            />
          </Card>
        </FadeUp>
      </div>
    );
  }

  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <FadeUp className="w-full max-w-md">
        <Card className="p-8 sm:p-10">
        {isSuccess ? (
          <FeedbackState
            icon={ShieldCheck}
            iconColorClass="bg-success/10 text-success"
            title="¡Contraseña actualizada!"
            description="Tu contraseña se ha cambiado correctamente. Todas tus sesiones anteriores han sido cerradas por seguridad."
            actionText="Iniciar Sesión"
            onAction={() => navigate('/login')}
          />
        ) : (
          <>
            <div className="mb-8 text-center flex flex-col items-center">
              <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-accent/10 text-accent">
                <Lock aria-hidden="true" size={32}/>
              </div>
              <h1 className="font-display text-h2 text-ink">Nueva contraseña</h1>
              <p className="mt-2 text-small text-ink-muted">
                Elige una contraseña segura para la cuenta asociada a <span className="font-semibold text-ink">{email}</span>.
              </p>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
              <Input
                label="Nueva contraseña"
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

              <Button
                type="submit"
                isLoading={isPending}
                isFullWidth
              >
                Actualizar contraseña
              </Button>
            </form>
          </>
        )}
        </Card>
      </FadeUp>
    </div>
  );
};

export default ResetPasswordPage;