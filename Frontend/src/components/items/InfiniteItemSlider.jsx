import React from 'react';
import { ItemCard } from './ItemCard.jsx';
import { InfiniteScroll } from '../animations/InfiniteScroll.jsx'; // Ajusta la ruta

const InfiniteItemSlider = ({ items = [] }) => {
  if (!items || items.length === 0) return null;

  const duplicatedItems = [...items, ...items];
  const scrollDuration = items.length * 3;

  return (
    <div className="relative flex w-full overflow-hidden py-8">
      <div className="absolute left-0 top-0 z-10 h-full w-12 bg-gradient-to-r from-slate-50 to-transparent sm:w-24" />
      <div className="absolute right-0 top-0 z-10 h-full w-12 bg-gradient-to-l from-slate-50 to-transparent sm:w-24" />

      <InfiniteScroll className="flex gap-6 px-6" duration={scrollDuration}>
        {duplicatedItems.map((item, index) => (
          <ItemCard key={`${item.id}-${index}`} item={item} />
        ))}
      </InfiniteScroll>
    </div>
  );
};

export default InfiniteItemSlider;