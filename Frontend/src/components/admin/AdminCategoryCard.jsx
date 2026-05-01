import React from 'react';
import { Edit2, Trash2 } from 'lucide-react';
import { AnimatedCard } from '../animations/AnimatedCard.jsx';

export const AdminCategoryCard = ({ category, onEdit, onDelete }) => {
  return (
    <AnimatedCard className="bg-white border border-slate-100 shadow-sm hover:shadow-md transition-shadow rounded-3xl p-6 flex flex-col gap-3 group relative overflow-hidden">
      <div className="flex flex-col gap-1">
        <h3 className="font-black text-xl text-slate-800 line-clamp-1">{category.name}</h3>
        <span className="text-xs font-bold text-slate-400">/{category.slug}</span>
      </div>
      <p className="text-sm text-slate-500 line-clamp-2 mt-1 min-h-[40px]">
        {category.description || "Sin descripción."}
      </p>

      <div className="flex gap-2 mt-4 pt-4 border-t border-slate-100">
        <button
          onClick={() => onEdit(category)}
          className="flex-1 flex justify-center items-center gap-2 bg-slate-50 text-slate-600 hover:bg-blue-50 hover:text-blue-600 py-2.5 rounded-xl text-sm font-bold transition-colors"
        >
          <Edit2 size={16} /> Editar
        </button>
        <button
          onClick={() => onDelete(category)}
          className="flex justify-center items-center bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-600 p-2.5 rounded-xl transition-colors"
          title="Eliminar"
        >
          <Trash2 size={18} />
        </button>
      </div>
    </AnimatedCard>
  );
};