import React from 'react';
import { Edit2, Trash2 } from 'lucide-react';
import { AnimatedCard } from '../animations/AnimatedCard.jsx';
import { Button } from '../common/Button';

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
        <Button
          onClick={() => onEdit(category)}
          variant="ghost"
          color="primary"
          icon={Edit2}
          isFullWidth={true}
        >
          Editar
        </Button>
        <Button
          onClick={() => onDelete(category)}
          variant="ghost"
          color="danger"
          size="iconMd"
          title="Eliminar"
          icon={Trash2}
        />
      </div>
    </AnimatedCard>
  );
};