import React, {useState} from 'react';
import {Link} from 'react-router-dom';
import {zodResolver} from '@hookform/resolvers/zod';
import {Mail, ArrowLeft, KeyRound, CheckCircle} from 'lucide-react';
import {forgotPasswordSchema} from '../../lib/schemas.js'
// Componentes
import InputField from '../../components/ui/InputField.jsx';
import Button from '../../components/ui/Button.jsx';
import ScaleFadeIn from '../../components/animations/ScaleFadeIn.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
// Hooks
import {useForgotPassword} from '../../hooks/auth/useAuthMutations.js';
import {useForm} from 'react-hook-form';

const ForgotPasswordPage = () => {
  const [isSubmitted, setIsSubmitted] = useState(false);
  const {mutate: sendResetLink, isPending} = useForgotPassword();

  const {register, handleSubmit, formState: {errors}} = useForm({
    resolver: zodResolver(forgotPasswordSchema)
  });

  const onSubmit = (data) => {
    sendResetLink(data, {
      onSuccess: () => setIsSubmitted(true)
    });
  };

  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <ScaleFadeIn className="w-full max-w-md rounded-3xl bg-surface p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">

        {isSubmitted ? (
          <FeedbackState
            icon={CheckCircle}
            iconColorClass="bg-green-100 text-green-500"
            title="Revisa tu correo"
            description="Si el email está registrado, recibirás un enlace para restablecer tu contraseña en unos minutos."
            actionText="Volver al inicio de sesión"
            onAction={() => window.location.href = '/login'}
          />
        ) : (
          <>
            <div className="mb-8 text-center flex flex-col items-center">
              <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-primary">
                <KeyRound size={32}/>
              </div>
              <h1 className="text-2xl font-black text-text-highlight">Recuperar contraseña</h1>
              <p className="mt-2 text-sm text-text-normal">
                Introduce tu correo electrónico y te enviaremos instrucciones para crear una nueva contraseña.
              </p>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
              <InputField
                label="Correo electrónico"
                type="email"
                icon={Mail}
                placeholder="tu@email.com"
                registration={register('email')}
                error={errors.email}
                disabled={isPending}
              />

              <Button
                type="submit"
                isLoading={isPending}
                isFullWidth
                variant="solid"
                color="primary"
              >
                Enviar enlace
              </Button>
            </form>

            <div className="mt-8 text-center">
              <Link to="/login"
                    className="inline-flex items-center text-sm font-bold text-text-normal hover:text-slate-800 transition-colors">
                <ArrowLeft size={16} className="mr-2"/>
                Volver al inicio de sesión
              </Link>
            </div>
          </>
        )}

      </ScaleFadeIn>
    </div>
  );
};

export default ForgotPasswordPage;