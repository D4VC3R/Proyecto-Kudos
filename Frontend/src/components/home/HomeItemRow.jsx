import React from 'react';
import ItemCard from '../items/ItemCard.jsx';

// Fila horizontal de tarjetas de la portada: 2,3, 4,3 o 6 a la vista según el ancho. En móvil sangra hasta el borde de la
// pantalla (`-mx-4`) para que se vea que se desliza. Sin ítems no pinta nada.
// `tabIndex={0}` deja desplazarla con el teclado (flechas), que si no solo podría quien tabula por sus tarjetas.
const HomeItemRow = ({ id, items = [], labelledBy, rowRef }) => {
  if (!items?.length) return null;

  return (
    <ul
      id={id}
      ref={rowRef}
      tabIndex={0}
      aria-labelledby={labelledBy}
      data-home-row
      className="scroll-track -mx-4 grid snap-x snap-mandatory auto-cols-row grid-flow-col gap-3 overflow-x-auto scroll-px-4 px-4 pb-2.5 pt-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:-mx-6 md:auto-cols-row-md md:gap-4 md:scroll-px-6 md:px-6 lg:mx-0 lg:auto-cols-row-lg lg:scroll-px-0 lg:px-0"
    >
      {items.map((item, index) => (
        <li key={item.id} className="min-w-0 snap-start">
          <ItemCard item={item} index={index} />
        </li>
      ))}
    </ul>
  );
};

export default HomeItemRow;
