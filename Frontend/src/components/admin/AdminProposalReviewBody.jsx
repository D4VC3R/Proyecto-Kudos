import React from 'react';
import { TextAreaField } from '../common/TextAreaField';

export const AdminProposalReviewBody = ({actionType, proposalName, adminNotes, setAdminNotes}) => {
  return (
    <>
      <p className="text-slate-600">
        Estás a punto de <span className="font-bold">{actionType === 'accepted' ? 'Aceptar' : 'Rechazar'}</span> la propuesta
        <span className="font-bold text-slate-900"> {proposalName}</span>.
      </p>

      <div className="mt-2">
        <TextAreaField
          label="Notas de revisión (Opcional/Requerido para rechazo)"
          placeholder="Escribe el motivo del rechazo o notas para el usuario..."
          value={adminNotes}
          onChange={(e) => setAdminNotes(e.target.value)}
        />
      </div>

      {actionType === 'accepted' && (
        <div className="p-3 mt-2 bg-blue-50 rounded-xl text-sm text-blue-700 font-medium">
          Al aceptar la propuesta, se creará un ítem público en la categoría correspondiente y el creador recibirá Kudos.
        </div>
      )}
    </>
  );
};