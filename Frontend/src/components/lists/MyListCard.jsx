import React from 'react';
import { Link } from 'react-router-dom';
import { Edit2, Eye, Heart, ShieldAlert, Trash2 } from 'lucide-react';
import Button from '../ui/Button.jsx';
import Card from '../ui/Card.jsx';
import Chip from '../ui/Chip.jsx';
import { hasText } from '../../lib/text.js';
import { LIST_VISIBILITY_LABELS } from '../../lib/lists.js';

// Tarjeta de una lista propia en "Mis listas" (F3-01).
// Sin collage de pósters: el índice de listas no trae los ítems.
const MyListCard = ({ list, onDelete }) => (
  <Card data-my-list-card className="flex h-full flex-col gap-3">
    <h2 className="line-clamp-1 font-display text-xl font-semibold leading-tight tracking-tight text-ink">{list.title}</h2>

    <p className="flex flex-wrap items-center gap-2 text-small text-ink-muted">
      <Chip>{LIST_VISIBILITY_LABELS[list.visibility]}</Chip>
      <span>
        <span className="tabular-nums">{list.items_count}</span> {list.items_count === 1 ? 'elemento' : 'elementos'}
      </span>
      <span className="inline-flex items-center gap-1">
        <Heart size={14} className="shrink-0" aria-hidden="true" />
        <span className="tabular-nums">{list.likes_count}</span> me gusta
      </span>
    </p>

    {hasText(list.description) && <p className="line-clamp-2 text-small text-ink-muted">{list.description}</p>}

    {list.is_hidden_by_moderation && (
      <p role="alert" className="inline-flex items-center gap-2 text-small font-semibold text-danger">
        <ShieldAlert size={16} className="shrink-0" />
        Ocultada por moderación
      </p>
    )}

    <div className="mt-auto flex gap-2 border-t border-line pt-3">
      <Button as={Link} to={`/mis-listas/${list.id}`} variant="secondary" icon={Edit2} className="flex-1">
        Editar
      </Button>
      <Button as={Link} to={`/listas/${list.slug}`} variant="ghost" icon={Eye} className="flex-1">
        Ver
      </Button>
      <Button
        onClick={() => onDelete(list)}
        variant="ghost"
        icon={Trash2}
        aria-label={`Eliminar «${list.title}»`}
        title="Eliminar"
        className="w-11 shrink-0 px-0 text-danger"
      />
    </div>
  </Card>
);

export default MyListCard;
