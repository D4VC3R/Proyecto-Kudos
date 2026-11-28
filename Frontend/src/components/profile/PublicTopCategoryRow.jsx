import React from 'react';
import ItemCard from '../items/ItemCard.jsx';

// Fila de los mejores ítems de un usuario en una categoría, con su nota debajo de cada tarjeta.
const PublicTopCategoryRow = ({ group }) => {
  if (!group?.items?.length) return null;

  const labelId = `top-${group.category.slug}`;

  return (
    <div className="flex flex-col gap-3">
      <h3 id={labelId} className="font-display text-h3 text-ink">{group.category.name}</h3>
      <ul aria-labelledby={labelId} className="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2">
        {group.items.map((entry, i) => (
          <li key={entry.item.id} className="w-36 shrink-0 snap-start sm:w-44">
            <div className="flex flex-col gap-2">
              <ItemCard item={entry.item} index={i} />
              <p className="text-small text-ink">Su nota: {entry.score}</p>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default PublicTopCategoryRow;
