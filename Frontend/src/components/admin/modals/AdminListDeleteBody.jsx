import React from 'react';

const AdminListDeleteBody = ({ listTitle }) => {
  return (
    <p className="text-nav-item">
      ¿Seguro que quieres eliminar la lista <span className="font-bold text-text-highlight">"{listTitle}"</span>?
      Dejará de verse en la portada.
    </p>
  );
};

export default AdminListDeleteBody;
