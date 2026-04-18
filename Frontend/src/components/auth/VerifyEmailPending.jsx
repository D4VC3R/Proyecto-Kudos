import React from 'react';
import { Mail } from 'lucide-react';

export const VerifyEmailPending = ({ onAction }) => {
  return (
    <>
      <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-blue-100 text-blue-500 shadow-inner">
        <Mail size={40} strokeWidth={2.5} />
      </div>
      <h1 className="text-2xl font-black text-slate-900 mb-2">Revisa tu bandeja</h1>
      <p className="text-slate-500 mb-6">Hemos enviado un enlace de confirmación a tu correo. Haz clic en él para validar tu cuenta y empezar a jugar.</p>
      <button onClick={onAction} className="w-full rounded-xl bg-slate-100 py-3 font-bold text-slate-700 transition-colors hover:bg-slate-200">
        Volver al Login
      </button>
    </>
  );
};

