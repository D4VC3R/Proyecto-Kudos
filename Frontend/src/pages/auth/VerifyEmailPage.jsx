import React, { useEffect, useState } from 'react';
import { useSearchParams, useNavigate, useLocation } from 'react-router-dom';
import axiosClient from '../../lib/axiosClient.js';
import { motion } from 'framer-motion';
import toast from 'react-hot-toast';
import { VerifyEmailVerifying } from '../../components/auth/VerifyEmailVerifying';
import { VerifyEmailSuccess } from '../../components/auth/VerifyEmailSuccess';
import { VerifyEmailPending } from '../../components/auth/VerifyEmailPending';
import { VerifyEmailError } from '../../components/auth/VerifyEmailError';

export const VerifyEmailPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [status, setStatus] = useState('verifying'); // 'verifying', 'success', 'error', 'pending'

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
        toast.success("¡Email verificado correctamente!");
        setTimeout(() => navigate('/'), 3000);
      } catch (error) {
        setStatus('error');
        toast.error(error.message || "Error al verificar tu email");
      }
    };

    verify();
  }, [searchParams, navigate, location.state]);

  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="w-full max-w-md rounded-3xl bg-white p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200 text-center flex flex-col items-center"
      >
        {status === 'verifying' && <VerifyEmailVerifying />}
        {status === 'success' && <VerifyEmailSuccess onAction={() => navigate('/login')} />}
        {status === 'pending' && <VerifyEmailPending onAction={() => navigate('/login')} />}
        {status === 'error' && <VerifyEmailError onAction={() => navigate('/login')} />}
      </motion.div>
    </div>
  );
};

export default VerifyEmailPage;

