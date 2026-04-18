import React from 'react';
import { FileText } from 'lucide-react';

export const MyProposalsEmpty = () => {
  return (
    <div className="flex flex-col items-center justify-center rounded-3xl bg-white p-12 text-center shadow-lg ring-1 ring-slate-200 min-h-[300px]">
      <div className="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 text-slate-300">
        <FileText size={40} />
      </div>
      <h3 className="text-xl font-bold text-slate-800">No tienes propuestas</h3>
      <p className="mt-2 text-slate-500 max-w-md">
        Aún no has sugerido ningún ítem. Dirígete a alguna categoría y crea tu primera propuesta para ganar Kudos.
      </p>
    </div>
  );
};
