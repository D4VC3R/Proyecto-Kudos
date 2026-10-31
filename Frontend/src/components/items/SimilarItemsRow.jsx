import React from 'react';
import ItemCard from './ItemCard.jsx';

// Fila "Similares" de la ficha (F2-08): tarjetas en scroll horizontal. Sin ítems no pinta nada.
const SimilarItemsRow = ({ items = [], onItemClick }) => {
  if (!items?.length) return null;

  return (
    <section aria-labelledby="similar-items-title" className="flex flex-col gap-3">
      <h3 id="similar-items-title" className="text-lg font-bold text-slate-800">Similares</h3>
      <ul className="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2">
        {items.map((similar) => (
          <li key={similar.id} className="w-36 shrink-0 snap-start sm:w-40">
            <ItemCard item={similar} onClick={() => onItemClick?.(similar)} />
          </li>
        ))}
      </ul>
    </section>
  );
};

export default SimilarItemsRow;
