import React, { useEffect, useMemo } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Loader2, MailCheck, Mail, AlertCircle } from 'lucide-react';
import { FeedbackState } from '../components/common/FeedbackState.jsx';
import { ScaleFadeIn } from "../components/animations/ScaleFadeIn.jsx";
import { useVerifyEmail } from '../hooks/auth/useAuthQueries.js';

const VerifyEmailPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  // Obtener la URL firmada que Laravel nos ha pasado como parámetro
  const verifyUrl = searchParams.get('verify_url');

  const { isLoading, isSuccess, isError } = useVerifyEmail(verifyUrl);

  // Efecto para mostrar notificaciones y redirigir después de la verificación
  useEffect(() => {
    if (isSuccess) {
      toast.success("¡Email verificado correctamente!");
      const timer = setTimeout(() => navigate('/login'), 3000);
      return () => clearTimeout(timer);
    }
    if (isError) {
      toast.error("El enlace ha expirado o es inválido.");
    }
  }, [isSuccess, isError, navigate]);

  const viewState = useMemo(() => {
    if (!verifyUrl) return 'initial';
    if (isLoading) return 'verifying';
    if (isSuccess) return 'success';
    return 'error';
  }, [verifyUrl, isLoading, isSuccess]);

  const STATE_CONFIG = {
    initial: {
      icon: Mail,
      iconColorClass: "h-20 w-20 rounded-full bg-blue-100 text-blue-500 shadow-inner",
      title: "Revisa tu bandeja de entrada",
      description: "Te hemos enviado un enlace de confirmación. Haz clic en él para validar tu cuenta de forma segura.",
      actionText: "Ir al Login",
      onAction: () => navigate('/login')
    },
    verifying: {
      icon: Loader2,
      isLoading: true,
      title: "Verificando tu cuenta",
      description: "Estableciendo conexión segura, por favor espera..."
    },
    success: {
      icon: MailCheck,
      iconColorClass: "h-20 w-20 rounded-full bg-green-100 text-green-500 shadow-inner",
      title: "¡Cuenta Verificada!",
      description: "Tu email ha sido validado correctamente. Ya puedes acceder a todas las funciones.",
      actionText: "Iniciar Sesión",
      onAction: () => navigate('/login'),
      actionColorClass: "bg-primary text-text-btn hover:bg-blue-700"
    },
    error: {
      icon: AlertCircle,
      iconColorClass: "h-20 w-20 rounded-full bg-red-100 text-red-500 shadow-inner",
      title: "Fallo de Verificación",
      description: "El enlace es inválido o ha expirado por seguridad. Inicia sesión en tu cuenta para solicitar un nuevo enlace de verificación.",
      actionText: "Ir al Login",
      onAction: () => navigate('/login'),
      actionColorClass: "bg-slate-800 text-text-btn hover:bg-slate-900"
    }
  };

  return (
      <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
        <ScaleFadeIn className="w-full max-w-md rounded-3xl bg-surface p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">
          <FeedbackState {...STATE_CONFIG[viewState]} />
        </ScaleFadeIn>
      </div>
  );
};

export default VerifyEmailPage;