import React from 'react';
import { motion } from 'framer-motion';
import { Star } from 'lucide-react';
import { bouncyCardVariants } from '../../lib/animations';

export const ItemCard = ({ item }) => {
  const displayScore = item.vote_avg ? Number(item.vote_avg).toFixed(1) : '-.-';

  return (
    <motion.article
      variants={bouncyCardVariants}
      whileHover="hover"
      whileTap="tap"
      className="group relative h-64 w-48 shrink-0 cursor-pointer overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-slate-200 transition-shadow hover:shadow-xl"
    >
      <div className="absolute inset-0">
        <img
          src={item.image}
          alt={item.name}
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent" />
      </div>
      
      <div className="absolute right-3 top-3">
        <div className="flex items-center gap-1 rounded-full bg-yellow-400 px-2 py-1 font-bold text-yellow-950 shadow-md ring-2 ring-yellow-300">
          <Star size={14} className="fill-yellow-600 stroke-yellow-700" />
          <span className="text-xs">{displayScore}</span>
        </div>
      </div>

      <div className="absolute inset-x-0 bottom-0 p-4">
        <h3 className="line-clamp-2 text-lg font-black tracking-tight text-white drop-shadow-md">
          {item.name}
        </h3>
      </div>
    </motion.article>
  );
};