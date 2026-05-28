import React from 'react';
import { Star, Info } from 'lucide-react';
import { ItemImage } from './ItemImage.jsx';

export const ItemDetailColumn = ({ item }) => {
  return (
    <div className="flex flex-col h-full w-full bg-surface rounded-3xl shadow-xl ring-1 ring-slate-200 p-6 md:p-8 lg:overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200">

      <div className="flex flex-col gap-6 mb-8 shrink-0">
        <h1 className="text-3xl md:text-5xl lg:text-6xl font-black text-text-highlight leading-tight">
          {item.name}
        </h1>

        <div className="flex flex-wrap items-center gap-3">
          <span className="flex items-center gap-2 rounded-full bg-yellow-50 px-4 py-2 text-sm font-bold text-yellow-800 ring-1 ring-yellow-300">
            <Star size={18} className="text-yellow-600 fill-yellow-500" />
            {item.vote_avg?.toFixed(1) || '0.0'} ({item.vote_count ?? 0} votos)
          </span>

          {item.creator && (
            <span className="flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200">
              <Info size={16} className="text-slate-500" />
              Aportado por <strong>{item.creator.name}</strong>
            </span>
          )}
        </div>
      </div>

      <div className="w-full flex justify-center mb-8 shrink-0">
        <ItemImage item={item} showTitle={false} />
      </div>

      <div className="prose prose-slate max-w-none mt-auto shrink-0">
        <div className="bg-slate-50 rounded-2xl border border-slate-100 p-6">
          <h3 className="text-lg font-bold text-slate-800 mb-3">Acerca de este candidato</h3>
          <p className="whitespace-pre-line text-lg text-slate-700 font-medium leading-relaxed">
            {item.description}
          </p>
        </div>
      </div>
    </div>
  );
};