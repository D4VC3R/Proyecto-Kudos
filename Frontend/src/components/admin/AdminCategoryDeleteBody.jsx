import React from 'react';

export const AdminCategoryDeleteBody = ({ categoryName }) => {
  return (
    <p className="text-slate-600">
      ¿Estás seguro de que deseas eliminar la categoría <span className="font-bold text-slate-900">{categoryName}</span>?
      <br/><br/>
      <span className="text-red-500 font-medium">Atención: Esta acción también eliminará todas las propuestas asociadas de forma permantente.</span>
    </p>
  );
};