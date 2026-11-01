import React from 'react';
import { Link } from 'react-router-dom';
import { hasText } from '../../lib/text.js';

// Tarjeta de una lista pública en el perfil de su autor (F3-03).
const PublicListCard = ({ list }) => (
  <div className="flex flex-col gap-2 rounded-3xl bg-surface p-4 shadow-xl ring-1 ring-border">
    <Link to={`/listas/${list.slug}`} className="break-words font-black text-text-highlight hover:underline">
      {list.title}
    </Link>
    {hasText(list.description) && <p className="line-clamp-2 text-sm text-text-normal">{list.description}</p>}
    <p className="text-sm font-bold text-text-normal">
      {list.items_count} títulos · {list.likes_count} me gusta
    </p>
  </div>
);

export default PublicListCard;
