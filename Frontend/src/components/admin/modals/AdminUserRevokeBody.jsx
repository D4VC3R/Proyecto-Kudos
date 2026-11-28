import React from 'react';

const AdminUserRevokeBody = ({ userName }) => {
  return (
    <p className="text-ink-muted">
      ¿Confirmas que deseas cerrar todas las sesiones del usuario <span className="font-bold">{userName}</span>?
      Esta acción lo desconectará de todos sus dispositivos.
    </p>
  );
};

export default AdminUserRevokeBody;