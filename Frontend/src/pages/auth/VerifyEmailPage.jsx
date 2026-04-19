import React, { useEffect, useState } from 'react';
import { useSearchParams, useNavigate, useLocation } from 'react-router-dom';
import axiosClient from '../../lib/axiosClient.js';
import { motion } from 'framer-motion';
import toast from 'react-hot-toast';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Loader2, MailCheck, Mail, AlertCircle } from 'lucide-react';
export const VerifyEmailPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [status, setStatus] = useState('verifying');
  useEffect(() => {
    const verifyUrl = searchParams.get('verify_url');
    if (!verifyUrl) {
      if (location.state?.registered) {
        setStatus('pending');
      } else {
        setStatus('error');
        toast.error("Url de verificación inválida");
      }
      return;
    }
    const verify = async () => {
      try {
        await axiosClient.get(verifyUrl);
        setStatus('success');
        toast.success("Email verificado correctamente!");
        setTimeout(() => navigate('/'), 3000);
      } catch (error) {
        setStatus('error');
        toast.error(error.message || "Error al verificar tu email");
      }
    };
    verify();
  }, [searchParams, navigate, location.state]);
  const renderContent = () => {
    switch (status) {
      case 'verifying':
        return (
          <FeedbackState
            icon={Loader2}
            isLoading={true}
            title="Verificando tu cuenta"
            description="Por favor, espera unos segundos..."
          />
        );
      case 'success':
        return (
          <FeedbackState
            icon={MailCheck}
            iconColorClass="h-20 w-20 rounded-full bg-green-100 text-green-500 shadow-inner"
            title="¡Todo listo!"
            description="Tu email ha sido verificado correctamente."
            actionText="Iniciar Sesión"
            onAction={() => navigate('/login')}
            actionColorClass="bg-blue-600 text-white hover:bg-blue-700"
          />
        );
      case 'pending':
        return (
          <FeedbackState
            icon={Mail}
            iconColorClass="h-20 w-20 rounded-full bg-blue-100 text-blue-500 shadow-inner"
            title="Revisa tu bandeja"
            description="Hemos enviado un enlace de confirmación a tu correo. Haz clic en él para validar tu cuenta y empezar a jugar."
            actionText="Volver al Login"
            onAction={() => navigate('/login')}
          />
        );
      case 'error':
        return (
          <FeedbackState
            icon={AlertCircle}
            iconColorClass="h-20 w-20 rounded-full bg-red-100 text-red-500 shadow-inner"
            title="Fallo de Verificación"
            description="El enlace es inválido, ha expirado, o no te encuentras autenticado."
            actionText="Iniciar Sesión"
            onAction={() => navigate('/login')}
          />
        );
      default:
        return null;
    }
  };
  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="w-full max-w-md rounded-3xl bg-white p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200"
      >
        {renderContent()}
      </motion.div>
    </div>
  );
};
export default VerifyEmailPage;
