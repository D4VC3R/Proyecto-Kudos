import React from 'react';
import { MailCheck } from 'lucide-react';

export const VerifyEmailSuccess = ({ onAction }) => {
  return (
    <>
      <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-green-500 shadow-inner">
        <MailCheck size={40} strokeWidth={2.5} />
      </div>
      <h1 className="text-2xl font-black text-slate-900 mb-2">¡Todo listo!</h1>
      <p className="text-slate-500 mb-6">Tu email ha sido verificado correctamente.</p>
      <button onClick={onAction} className="w-full rounded-xl bg-blue-600 py-3 font-bold text-white transition-colors hover:bg-blue-700">
        Iniciar Sesión
      </button>
    </>
  );
};

