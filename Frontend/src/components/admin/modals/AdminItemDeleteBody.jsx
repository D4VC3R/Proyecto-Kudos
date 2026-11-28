import React from 'react';

const AdminItemDeleteBody = ({ itemName }) => {
  return (
    <p className="text-ink-muted">
      ¿Estás seguro de eliminar el ítem <span className="font-bold text-ink">{itemName}</span>?
      Se perderán todos los votos y comentarios.
    </p>
  );
};

export default AdminItemDeleteBody;