import React from 'react';
import { motion } from 'framer-motion';
import { ItemCard } from '../items/ItemCard.jsx';

const InfiniteSlider = ({ items = [] }) => {
  if (!items || items.length === 0) return null;

  // Duplicamos el array para crear la ilusión de loop infinito
  const duplicatedItems = [...items, ...items];

  return (
    <div className="relative flex w-full overflow-hidden py-8">
      <div className="absolute left-0 top-0 z-10 h-full w-12 bg-gradient-to-r from-slate-50 to-transparent sm:w-24" />
      <div className="absolute right-0 top-0 z-10 h-full w-12 bg-gradient-to-l from-slate-50 to-transparent sm:w-24" />

      <motion.div
        className="flex gap-6 px-6"
        animate={{
          x: ['0%', '-50%'],
        }}
        transition={{
          ease: 'linear',
          duration: items.length * 3,
          repeat: Infinity,
        }}
      >
        {duplicatedItems.map((item, index) => (
          <ItemCard key={`${item.id}-${index}`} item={item} />
        ))}
      </motion.div>
    </div>
  );
};

export default InfiniteSlider;