import React from 'react';

const AdminCategoryDeleteBody = ({ categoryName }) => {
  return (
    <p className="text-ink-muted">
      ¿Estás seguro de que deseas eliminar la categoría <span className="font-bold text-ink">{categoryName}</span>?
      <br/><br/>
      <span className="text-danger font-medium">Atención: Esta acción también eliminará todos los ítems y propuestas asociadas de forma permantente.</span>
    </p>
  );
};

export default AdminCategoryDeleteBody;