import React from 'react';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
import { hasText } from '../../lib/text.js';
import Card from '../ui/Card.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';

// Tarjeta de una lista pública en el perfil de su autor.
const PublicListCard = ({ list }) => (
  <Card as="li" className="flex flex-col gap-2">
    <h3 className="break-words font-display text-h3 text-ink">
      <Link to={`/listas/${list.slug}`} className={clsx('rounded-sm underline-offset-3 hover:underline', FOCUS_RING)}>
        {list.title}
      </Link>
    </h3>
    {hasText(list.description) && <p className="line-clamp-2 text-small text-ink-muted">{list.description}</p>}
    <p className="text-small text-ink-muted">
      {list.items_count === 1 ? '1 elemento' : `${list.items_count} elementos`} · {list.likes_count} me gusta
    </p>
  </Card>
);

export default PublicListCard;
