import React from 'react';
import { Link } from 'react-router-dom';
import { Edit2, ListOrdered, Trash2 } from 'lucide-react';
import AnimatedCard from '../animations/AnimatedCard.jsx';
import Button from '../ui/Button.jsx';
import { hasText } from '../../lib/text.js';

// Tarjeta de una lista editorial en el panel de administración (F2-09).
const AdminListCard = ({ list, onEdit, onDelete }) => {
  return (
    <AnimatedCard className="bg-surface border border-slate-100 shadow-sm hover:shadow-md transition-shadow rounded-3xl p-6 flex flex-col gap-3 group relative overflow-hidden">
      <div className="flex flex-col gap-1">
        <h3 className="font-black text-xl text-slate-800 line-clamp-1">{list.title}</h3>
        <span className="text-xs font-bold text-slate-400">/listas/{list.slug}</span>
      </div>
      <p className="text-sm font-bold text-text-normal">{list.items_count} títulos</p>
      {hasText(list.description) && (
        <p className="text-sm text-text-normal line-clamp-2">{list.description}</p>
      )}

      <div className="flex gap-2 mt-4 pt-4 border-t border-slate-100">
        <Link
          to={`/admin/lists/${list.id}`}
          className="inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2 font-bold text-primary transition-colors hover:bg-blue-50"
        >
          <ListOrdered size={18} className="shrink-0" />
          Ítems
        </Link>
        <Button
          onClick={() => onEdit(list)}
          variant="ghost"
          color="primary"
          icon={Edit2}
        >
          Editar
        </Button>
        <Button
          onClick={() => onDelete(list)}
          variant="ghost"
          color="danger"
          size="iconMd"
          title="Eliminar"
          aria-label="Eliminar lista"
          icon={Trash2}
        />
      </div>
    </AnimatedCard>
  );
};

export default AdminListCard;
