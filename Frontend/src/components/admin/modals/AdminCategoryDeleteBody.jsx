import React from 'react';

const AdminCategoryDeleteBody = ({ categoryName }) => {
  return (
    <p className="text-nav-item">
      ¿Estás seguro de que deseas eliminar la categoría <span className="font-bold text-text-highlight">{categoryName}</span>?
      <br/><br/>
      <span className="text-red-500 font-medium">Atención: Esta acción también eliminará todos los ítems y propuestas asociadas de forma permantente.</span>
    </p>
  );
};

export default AdminCategoryDeleteBody;