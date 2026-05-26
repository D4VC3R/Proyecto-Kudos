import React from 'react';

export const AdminUserRevokeBody = ({ userName }) => {
  return (
    <p className="text-slate-600">
      ¿Confirmas que deseas cerrar todas las sesiones del usuario <span className="font-bold">{userName}</span>?
      Esta acción lo desconectará de todos sus dispositivos.
    </p>
  );
};