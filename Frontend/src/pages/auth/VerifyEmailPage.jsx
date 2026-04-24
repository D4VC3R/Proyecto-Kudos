import React, { useEffect } from 'react';
import { useSearchParams, useNavigate, useLocation } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Loader2, MailCheck, Mail, AlertCircle } from 'lucide-react';
import { FeedbackState } from '../../components/common/FeedbackState';
import { ScaleFadeIn } from "../../components/animations/ScaleFadeIn";
import { useVerifyEmail } from '../../hooks/auth/useAuthQueries';

const VerifyEmailPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const location = useLocation();

  const verifyUrl = searchParams.get('verify_url');

  const { isLoading, isSuccess, isError } = useVerifyEmail(verifyUrl);

  useEffect(() => {
    if (isSuccess) {
      toast.success("¡Email verificado correctamente!");
      const timer = setTimeout(() => navigate('/'), 3000);
      return () => clearTimeout(timer);
    }
    if (isError) {
      toast.error("Error al verificar el enlace.");
    }
  }, [isSuccess, isError, navigate]);

  let viewState = 'initial';
  if (isLoading) viewState = 'verifying';
  else if (isSuccess) viewState = 'success';
  else if (isError || (!verifyUrl && !location.state?.registered)) viewState = 'error';

  const STATE_CONFIG = {
    verifying: {
      icon: Loader2,
      isLoading: true,
      title: "Verificando tu cuenta",
      description: "Por favor, espera unos segundos..."
    },
    success: {
      icon: MailCheck,
      iconColorClass: "h-20 w-20 rounded-full bg-green-100 text-green-500 shadow-inner",
      title: "¡Todo listo!",
      description: "Tu email ha sido verificado correctamente.",
      actionText: "Iniciar Sesión",
      onAction: () => navigate('/login'),
      actionColorClass: "bg-blue-600 text-white hover:bg-blue-700"
    },
    error: {
      icon: AlertCircle,
      iconColorClass: "h-20 w-20 rounded-full bg-red-100 text-red-500 shadow-inner",
      title: "Fallo de Verificación",
      description: "El enlace es inválido, ha expirado, o no te encuentras autenticado.",
      actionText: "Iniciar Sesión",
      onAction: () => navigate('/login')
    },
    initial: {
      icon: Mail,
      iconColorClass: "h-20 w-20 rounded-full bg-blue-100 text-blue-500 shadow-inner",
      title: "Revisa tu bandeja",
      description: "Hemos enviado un enlace de confirmación a tu correo. Haz clic en él para validar tu cuenta.",
      actionText: "Volver al Login",
      onAction: () => navigate('/login')
    }
  };

  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <ScaleFadeIn className="w-full max-w-md rounded-3xl bg-white p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">
        <FeedbackState {...STATE_CONFIG[viewState]} />
      </ScaleFadeIn>
    </div>
  );
};

export default VerifyEmailPage;