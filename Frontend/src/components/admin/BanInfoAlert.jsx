import React from 'react';
import { Ban } from 'lucide-react';
import { formatDate } from '../../lib/formatters.js';

export const BanInfoAlert = ({ banReason, bannedAt, bannedUntil, banState }) => {
  return (
    <div className="mt-4 p-4 bg-red-50 border border-red-100 rounded-2xl flex flex-col gap-2">
      <div className="flex items-center gap-2 text-red-700 font-bold">
        <Ban size={16} /> Información de Bloqueo
      </div>
      {banReason && (
        <p className="text-sm text-red-600 mt-1"><strong>Motivo:</strong> {banReason}</p>
      )}
      {bannedAt && (
        <p className="text-xs text-red-500">Bloqueado el: {formatDate(bannedAt)}</p>
      )}
      {bannedUntil && (
        <p className="text-xs text-red-500">Expira: {formatDate(bannedUntil)}</p>
      )}
      {banState === 'permanent' && (
        <p className="text-xs text-red-500 font-bold uppercase mt-1">Bloqueo Permanente</p>
      )}
    </div>
  );
};

