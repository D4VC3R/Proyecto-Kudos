import React from 'react';
import ItemCard from '../items/ItemCard.jsx';

// Fila horizontal de tarjetas de la portada (F2-09), con la misma forma que "Similares". Sin ítems no pinta nada.
const HomeItemRow = ({ items = [], label, labelId }) => {
  if (!items?.length) return null;

  return (
    <div className="flex flex-col gap-3">
      {label && <h3 id={labelId} className="text-lg font-bold text-slate-800">{label}</h3>}
      <ul aria-labelledby={label ? labelId : undefined} className="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2">
        {items.map((item) => (
          <li key={item.id} className="w-36 shrink-0 snap-start sm:w-44">
            <ItemCard item={item} />
          </li>
        ))}
      </ul>
    </div>
  );
};

export default HomeItemRow;
