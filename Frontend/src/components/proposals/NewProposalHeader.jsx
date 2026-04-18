import React from 'react';
import { FilePlus } from 'lucide-react';

export const NewProposalHeader = ({ categoryName }) => {
  return (
    <div className="mb-10 flex flex-col items-center text-center">
      <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-100 text-red-600 shadow-inner">
        <FilePlus size={32} strokeWidth={2.5} />
      </div>
      <h1 className="text-3xl font-black text-slate-900 tracking-tight">
        Crear <span className="text-red-500 drop-shadow-sm">Nueva Propuesta</span>
      </h1>
      <p className="mt-3 text-base font-medium text-slate-500 max-w-md mx-auto">
        Ayuda a expandir el universo de {categoryName} proponiendo nuevos ítems. Un embajador revisará tu idea para validarla.
      </p>
    </div>
  );
};

