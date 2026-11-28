import React from 'react';
import Textarea from '../../ui/Textarea.jsx';

const AdminProposalReviewBody = ({actionType, proposalName, adminNotes, setAdminNotes}) => {
  return (
    <>
      <p className="text-ink-muted">
        Estás a punto de <span className="font-bold">{actionType === 'accepted' ? 'Aceptar' : 'Rechazar'}</span> la propuesta
        <span className="font-bold text-ink"> {proposalName}</span>.
      </p>

      <div className="mt-2">
        <Textarea
          label="Notas de revisión"
          placeholder="Escribe el motivo del rechazo o notas para el usuario..."
          value={adminNotes}
          onChange={(e) => setAdminNotes(e.target.value)}
        />
      </div>

      {actionType === 'accepted' && (
        <div className="mt-2 rounded-md border border-line bg-canvas p-3 text-small text-ink">
          Al aceptar la propuesta, se creará un ítem público en la categoría correspondiente y el creador recibirá Kudos.
        </div>
      )}
    </>
  );
};

export default AdminProposalReviewBody;