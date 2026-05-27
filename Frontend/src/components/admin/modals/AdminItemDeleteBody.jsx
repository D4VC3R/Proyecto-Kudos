import React from 'react';

export const AdminItemDeleteBody = ({ itemName }) => {
  return (
    <p className="text-nav-item">
      ¿Estás seguro de eliminar el ítem <span className="font-bold text-text-highlight">{itemName}</span>?
      Se perderán todos los votos y comentarios.
    </p>
  );
};