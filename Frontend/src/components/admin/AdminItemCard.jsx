import React from 'react';
import { Trash2 } from 'lucide-react';
import {statusColors} from "../../lib/constants.js";
import {formatStatus} from "../../lib/formatters.js";
import { clsx } from 'clsx';
import AnimatedCard from '../animations/AnimatedCard.jsx';
import Button from '../ui/Button.jsx';

const AdminItemCard = ({ item, onModerate, onDelete }) => {
  return (
    <AnimatedCard className="bg-surface border border-slate-100 shadow-sm hover:shadow-md transition-shadow rounded-3xl p-5 flex flex-col gap-3 group relative overflow-hidden">
      <div className="flex justify-between items-start gap-2">
        <h3 className="font-black text-slate-800 line-clamp-1">{item.name}</h3>
        <span className={clsx("px-2 py-0.5 rounded-lg text-[10px] font-black uppercase whitespace-nowrap", statusColors[item.status] || "bg-slate-100 text-slate-700")}>
          {formatStatus(item.status)}
        </span>
      </div>
      <p className="text-sm text-text-normal line-clamp-2 mt-1">{item.description}</p>

      <div className="flex flex-col gap-1 mt-2 text-xs font-medium text-text-normal">
        <div>Puntuación: <span className="font-bold text-yellow-600">{item.vote_avg} ({item.vote_count} votos)</span></div>
        <div>Categoría: <span className="font-bold text-slate-700">{item.category?.name}</span></div>
      </div>

      <div className="flex gap-2 mt-auto pt-4 border-t border-slate-100">
        <Button
          onClick={() => onModerate(item)}
          variant="ghost"
          color="primary"
          isFullWidth={true}
        >
          Moderar
        </Button>
        <Button
          onClick={() => onDelete(item)}
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

export default AdminItemCard;