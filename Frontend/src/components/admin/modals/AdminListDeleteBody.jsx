import React from 'react';

const AdminListDeleteBody = ({ listTitle }) => {
  return (
    <p className="text-ink-muted">
      ¿Seguro que quieres eliminar la lista <span className="font-bold text-ink">"{listTitle}"</span>?
      Dejará de verse en la portada.
    </p>
  );
};

export default AdminListDeleteBody;
