import React from 'react';
import { ItemCard } from './ItemCard.jsx';
import { InfiniteSlider } from '../animations/InfiniteSlider.jsx'; // Ajusta la ruta

const InfiniteItemSlider = ({ items = [], onItemClick }) => {
  if (!items || items.length === 0) return null;

  const duplicatedItems = [...items, ...items];
  const scrollDuration = items.length * 3;

  return (
    <div className="relative flex w-full overflow-hidden py-8">
      <div className="absolute left-0 top-0 z-10 h-full w-12 bg-gradient-to-r from-slate-50 to-transparent sm:w-24" />
      <div className="absolute right-0 top-0 z-10 h-full w-12 bg-gradient-to-l from-slate-50 to-transparent sm:w-24" />

      <InfiniteSlider className="flex gap-6 px-6" duration={scrollDuration}>
        {duplicatedItems.map((item, index) => (
          <div key={`${item.id}-${index}`} className="w-40 shrink-0 sm:w-48">
            <ItemCard item={item} onClick={() => onItemClick && onItemClick(item)} />
          </div>
        ))}
      </InfiniteSlider>
    </div>
  );
};

export default InfiniteItemSlider;