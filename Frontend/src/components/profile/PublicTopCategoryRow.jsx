import React from 'react';
import ItemCard from '../items/ItemCard.jsx';

// Fila de los mejores ítems de un usuario en una categoría (F3-03), con su nota debajo de cada tarjeta.
const PublicTopCategoryRow = ({ group, onItemClick }) => {
  if (!group?.items?.length) return null;

  const labelId = `top-${group.category.slug}`;

  return (
    <div className="flex flex-col gap-3">
      <h3 id={labelId} className="text-lg font-bold text-slate-800">{group.category.name}</h3>
      <ul aria-labelledby={labelId} className="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2">
        {group.items.map((entry) => (
          <li key={entry.item.id} className="w-36 shrink-0 snap-start sm:w-44">
            <div className="flex flex-col gap-2">
              <ItemCard item={entry.item} onClick={() => onItemClick?.(entry.item)} />
              <p className="text-sm font-bold text-text-normal">Su nota: {entry.score}</p>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default PublicTopCategoryRow;
