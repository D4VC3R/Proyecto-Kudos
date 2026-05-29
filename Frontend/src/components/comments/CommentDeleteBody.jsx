import React from 'react';

const CommentDeleteBody = () => (
  <div className="flex flex-col gap-2">
    <p className="text-nav-item">
      ¿Estás seguro de que deseas eliminar este comentario permanentemente?
    </p>
    <p className="text-sm text-text-normal italic">
      Esta acción no se puede deshacer.
    </p>
  </div>
);

export default CommentDeleteBody;