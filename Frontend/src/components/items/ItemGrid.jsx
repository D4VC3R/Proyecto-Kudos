import React from 'react';
import clsx from 'clsx';
import ItemCard from './ItemCard.jsx';

// Rejilla de tarjetas de Explorar y de los resultados de búsqueda: mismas columnas y huecos en las dos páginas.
export const ITEM_GRID_CLASSES = 'grid grid-cols-2 gap-x-3 gap-y-6 sm:grid-cols-3 md:grid-cols-4 md:gap-x-4 md:gap-y-8 lg:grid-cols-6';

const ItemGrid = ({ items, className, ...props }) => (
  <ul className={clsx(ITEM_GRID_CLASSES, className)} {...props}>
    {items.map((item, index) => (
      <li key={item.id} className="min-w-0">
        <ItemCard item={item} index={index} />
      </li>
    ))}
  </ul>
);

export default ItemGrid;
