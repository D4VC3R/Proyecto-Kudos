import React from 'react';

export const CommentDeleteBody = () => (
  <div className="flex flex-col gap-2">
    <p className="text-slate-600">
      ¿Estás seguro de que deseas eliminar este comentario permanentemente?
    </p>
    <p className="text-sm text-slate-500 italic">
      Esta acción no se puede deshacer.
    </p>
  </div>
);