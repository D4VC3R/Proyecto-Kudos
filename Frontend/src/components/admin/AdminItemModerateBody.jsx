import React from 'react';
import { TextAreaField } from '../common/TextAreaField';

export const AdminItemModerateBody = ({itemName, modStatus, setModStatus, modReason, setModReason}) => {
  return (
    <>
      <p className="text-slate-600 text-sm">
        Estás moderando <span className="font-bold text-slate-900">{itemName}</span>. Selecciona un nuevo estado.
      </p>
      <div className="flex flex-col gap-2">
        <label className="text-sm font-bold text-slate-700">Nuevo Estado</label>
        <select
          className="rounded-2xl border-slate-200 bg-slate-50 p-3 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 font-bold text-slate-700"
          value={modStatus}
          onChange={(e) => setModStatus(e.target.value)}
        >
          <option value="active">Activo</option>
          <option value="inactive">Inactivo</option>
        </select>
      </div>
      <div className="mt-2">
        <TextAreaField
          label={`Razón ${modStatus === 'inactive' ? '(Obligatoria al desactivar)' : ''}`}
          placeholder="Motivo de la moderación para el historial..."
          value={modReason}
          onChange={(e) => setModReason(e.target.value)}
          required={modStatus === 'inactive'}
        />
      </div>
    </>
  );
};