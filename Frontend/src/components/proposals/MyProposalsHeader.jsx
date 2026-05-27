import React from 'react';
import {SectionHeader} from "../common/SectionHeader.jsx";

export const MyProposalsHeader = ({ meta }) => {
  return (
    <div className="flex flex-col md:flex-row md:items-end justify-between border-b border-border pb-4">
      <SectionHeader
        title="Mis"
        highlight="Propuestas"
        subtitle="Sigue el estado de los ítems que has sugerido para Kudos."
      >

      <div className="mt-4 md:mt-0 flex gap-3 text-sm font-bold text-nav-item">
        <div className="flex flex-col items-center p-2 bg-background rounded-xl min-w-[70px]">
          <span className="text-xl text-text-highlight">{meta.total || 0}</span>
          <span className="text-[10px] uppercase text-slate-400">Total</span>
        </div>
        <div className="flex flex-col items-center p-2 bg-green-50 rounded-xl min-w-[70px] border border-green-100">
          <span className="text-xl text-green-700">{meta.accepted || 0}</span>
          <span className="text-[10px] uppercase text-green-500">Aceptadas</span>
        </div>
        <div className="flex flex-col items-center p-2 bg-yellow-50 rounded-xl min-w-[70px] border border-yellow-100">
          <span className="text-xl text-yellow-700">{meta.pending || 0}</span>
          <span className="text-[10px] uppercase text-yellow-500">Pendientes</span>
        </div>
      </div>
      </SectionHeader>
    </div>
  );
};
