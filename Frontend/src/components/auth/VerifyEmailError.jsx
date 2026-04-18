import React from 'react';
import { AlertCircle } from 'lucide-react';

export const VerifyEmailError = ({ onAction }) => {
  return (
    <>
      <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100 text-red-500 shadow-inner">
        <AlertCircle size={40} strokeWidth={2.5} />
      </div>
      <h1 className="text-2xl font-black text-slate-900 mb-2">Fallo de Verificación</h1>
      <p className="text-slate-500 mb-6">El enlace es inválido, ha expirado, o no te encuentras autenticado.</p>
      <button onClick={onAction} className="w-full rounded-xl bg-slate-100 py-3 font-bold text-slate-700 transition-colors hover:bg-slate-200">
        Iniciar Sesión
      </button>
    </>
  );
};

