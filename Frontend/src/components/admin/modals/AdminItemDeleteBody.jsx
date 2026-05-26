import React from 'react';

export const AdminItemDeleteBody = ({ itemName }) => {
  return (
    <p className="text-slate-600">
      ¿Estás seguro de eliminar el ítem <span className="font-bold text-slate-900">{itemName}</span>?
      Se perderán todos los votos y comentarios.
    </p>
  );
};