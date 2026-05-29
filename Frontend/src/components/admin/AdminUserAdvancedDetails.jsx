import React from 'react';
import  SectionHeader  from '../ui/SectionHeader.jsx';
import { Calendar } from 'lucide-react';
import { formatDate } from '../../lib/formatters.js';

const AdminUserAdvancedDetails = ({ user }) => {
  return (
    <>
      <SectionHeader title="Detalles" highlight="Avanzados" icon={Calendar} />
      <div className="bg-surface border border-slate-100 shadow-sm rounded-3xl p-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
          <div className="flex flex-col">
            <span className="text-xs font-bold text-text-normal uppercase">ID Usuario</span>
            <span className="text-sm font-medium text-slate-700 truncate">{user.id}</span>
          </div>
          <div className="flex flex-col">
            <span className="text-xs font-bold text-text-normal uppercase">Email Verificado</span>
            <span className="text-sm font-medium text-slate-700">
              {user.is_verified ? 'Sí' : 'No'}
              {user.email_verified_at && <span className="text-xs text-slate-400 ml-2 font-normal">({formatDate(user.email_verified_at)})</span>}
            </span>
          </div>
           <div className="flex flex-col">
            <span className="text-xs font-bold text-text-normal uppercase">Ciudad</span>
            <span className="text-sm font-medium text-slate-700">
              {user.profile?.city || 'No especificada'}
            </span>
          </div>
          <div className="flex flex-col">
            <span className="text-xs font-bold text-text-normal uppercase">Fecha de Nacimiento</span>
            <span className="text-sm font-medium text-slate-700">
              {user.profile?.birthdate ? formatDate(user.profile.birthdate) : 'No especificada'}
            </span>
          </div>
        </div>
      </div>
    </>
  );
};

export default AdminUserAdvancedDetails;
