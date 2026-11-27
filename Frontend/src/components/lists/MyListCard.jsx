import React from 'react';
import { Link } from 'react-router-dom';
import { Edit2, Eye, Heart, ShieldAlert, Trash2 } from 'lucide-react';
import AnimatedCard from '../animations/AnimatedCard.jsx';
import Button from '../ui/Button.jsx';
import { hasText } from '../../lib/text.js';
import { LIST_VISIBILITY_LABELS } from '../../lib/lists.js';

// Tarjeta de una lista propia en "Mis listas" (F3-01).
const MyListCard = ({ list, onDelete }) => (
  <AnimatedCard className="relative flex flex-col gap-3 overflow-hidden rounded-3xl border border-slate-100 bg-surface p-6 shadow-sm transition-shadow hover:shadow-md">
    <div className="flex flex-col gap-1">
      <h3 className="line-clamp-1 text-xl font-black text-slate-800">{list.title}</h3>
      <span className="text-xs font-bold text-slate-400">{LIST_VISIBILITY_LABELS[list.visibility]}</span>
    </div>

    <div className="flex items-center gap-4 text-sm font-bold text-text-normal">
      <span>{list.items_count} títulos</span>
      <span className="inline-flex items-center gap-1">
        <Heart size={14} className="shrink-0" />
        {list.likes_count} me gusta
      </span>
    </div>

    {hasText(list.description) && <p className="line-clamp-2 text-sm text-text-normal">{list.description}</p>}

    {list.is_hidden_by_moderation && (
      <p role="alert" className="inline-flex items-center gap-2 text-sm font-bold text-red-600">
        <ShieldAlert size={16} className="shrink-0" />
        Ocultada por moderación
      </p>
    )}

    <div className="mt-4 flex gap-2 border-t border-slate-100 pt-4">
      <Button as={Link} to={`/mis-listas/${list.id}`} variant="ghost" icon={Edit2} className="flex-1">
        Editar
      </Button>
      <Button as={Link} to={`/listas/${list.slug}`} variant="ghost" icon={Eye} className="flex-1">
        Ver
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

export default MyListCard;
