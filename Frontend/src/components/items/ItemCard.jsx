import React from 'react';
import { Star } from 'lucide-react';
import { BouncyCard } from '../animations/BouncyCard';
import StorageImage from "../common/StorageImage.jsx";

export const ItemCard = ({ item, onClick }) => {
  const displayScore = item.vote_avg ? Number(item.vote_avg).toFixed(1) : '-.-';
  const imagePath = item.images?.[0]?.variants?.thumb;


  return (
    <BouncyCard
      onClick={onClick}
      className="group relative h-64 w-48 shrink-0 cursor-pointer overflow-hidden rounded-3xl bg-surface shadow-lg ring-1 ring-slate-200 transition-shadow hover:shadow-xl"
    >
      <div className="absolute inset-0">
        <StorageImage
          src={imagePath}
          alt={item.name}
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent" />
      </div>
      
      <div className="absolute right-3 top-3">
        <div className="flex items-center gap-1 rounded-full bg-accent px-2 py-1 font-bold text-yellow-950 shadow-md ring-2 ring-yellow-300">
          <Star size={14} className="fill-yellow-600 stroke-yellow-700" />
          <span className="text-xs">{displayScore}</span>
        </div>
      </div>

      <div className="absolute inset-x-0 bottom-0 p-4">
        <h3 className="line-clamp-2 text-lg font-black tracking-tight text-text-btn drop-shadow-md">
          {item.name}
        </h3>
      </div>
    </BouncyCard>
  );
};