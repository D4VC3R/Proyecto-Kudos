import React from 'react';
import { Ban } from 'lucide-react';
import { formatDate } from '../../lib/formatters.js';

const BanInfoAlert = ({ banReason, bannedAt, bannedUntil, banState }) => {
  return (
    <div className="flex flex-col gap-2 rounded-md border border-danger bg-danger/10 p-4">
      <div className="flex items-center gap-2 text-danger font-semibold">
        <Ban size={16} aria-hidden="true" /> Información de bloqueo
      </div>
      {banReason && (
        <p className="text-small text-ink"><strong>Motivo:</strong> {banReason}</p>
      )}
      {bannedAt && (
        <p className="text-small text-ink">Bloqueado el: {formatDate(bannedAt)}</p>
      )}
      {bannedUntil && (
        <p className="text-small text-ink">Expira: {formatDate(bannedUntil)}</p>
      )}
      {banState === 'permanent' && (
        <p className="text-small font-semibold uppercase text-ink">Bloqueo permanente</p>
      )}
    </div>
  );
};

export default BanInfoAlert;
