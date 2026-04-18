import React from 'react';
import { Loader2 } from 'lucide-react';

export const VerifyEmailVerifying = () => {
  return (
    <>
      <Loader2 className="h-16 w-16 animate-spin text-blue-500 mb-6" />
      <h1 className="text-2xl font-black text-slate-900 mb-2">Verificando tu cuenta</h1>
      <p className="text-slate-500">Por favor, espera unos segundos...</p>
    </>
  );
};

