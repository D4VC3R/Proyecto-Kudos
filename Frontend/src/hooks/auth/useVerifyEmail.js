import {useEffect, useMemo} from 'react';
import {useSearchParams, useNavigate} from 'react-router-dom';
import toast from 'react-hot-toast';
import {Loader2, MailCheck, Mail, AlertCircle} from 'lucide-react';
import {useVerifyEmail as useVerifyEmailQuery} from './useAuthQueries';

/**
 * Custom hook para manejar la lógica de verificación de email.
 * Extrae el parámetro de verificación de la URL, realiza la consulta de verificación y maneja los estados de carga, éxito y error.
 * Devuelve un objeto con la información necesaria para mostrar el estado actual de la verificación en la interfaz de usuario.
 */
const useVerifyEmail = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const verifyUrl = searchParams.get('verify_url');

  const {isLoading, isSuccess, isError} = useVerifyEmailQuery(verifyUrl);

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

  return useMemo(() => {
    if (!verifyUrl) {
      return {
        icon: Mail,
        iconColorClass: "bg-blue-100 text-blue-500 shadow-inner",
        title: "Revisa tu bandeja de entrada",
        description: "Te hemos enviado un enlace de confirmación. Haz clic en él para validar tu cuenta de forma segura.",
        actionText: "Ir al Login",
        onAction: () => navigate('/login')
      };
    }

    if (isLoading) {
      return {
        icon: Loader2,
        isLoading: true,
        title: "Verificando tu cuenta",
        description: "Estableciendo conexión segura, por favor espera..."
      };
    }

    if (isSuccess) {
      return {
        icon: MailCheck,
        iconColorClass: "bg-green-100 text-green-500 shadow-inner",
        title: "¡Cuenta Verificada!",
        description: "Tu email ha sido validado correctamente. Ya puedes acceder a todas las funciones.",
        actionText: "Iniciar Sesión",
        onAction: () => navigate('/login')
      };
    }

    return {
      icon: AlertCircle,
      iconColorClass: "bg-red-100 text-red-500 shadow-inner",
      title: "Fallo de Verificación",
      description: "El enlace es inválido o ha expirado por seguridad. Inicia sesión en tu cuenta para solicitar un nuevo enlace.",
      actionText: "Ir al Login",
      onAction: () => navigate('/login')
    };
  }, [verifyUrl, isLoading, isSuccess, navigate]);
};

export default useVerifyEmail;