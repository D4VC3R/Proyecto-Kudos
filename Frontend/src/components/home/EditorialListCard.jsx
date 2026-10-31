import React from 'react';
import { Link } from 'react-router-dom';
import HomeItemRow from './HomeItemRow.jsx';
import { hasText } from '../../lib/text.js';

// Tarjeta de una lista editorial en la portada (F2-09): título, descripción y sus primeros ítems.
const EditorialListCard = ({ list, onItemClick }) => (
  <article className="flex flex-col gap-3 rounded-3xl bg-surface p-4 shadow-xl ring-1 ring-border sm:p-6">
    <h3 className="text-xl font-black text-text-highlight">
      <Link to={`/listas/${list.slug}`} className="hover:text-primary">{list.title}</Link>
    </h3>
    {hasText(list.description) && (
      <p className="line-clamp-2 text-sm font-medium text-text-normal">{list.description}</p>
    )}
    <HomeItemRow items={(list.items ?? []).map((entry) => entry.item)} onItemClick={onItemClick} />
    <Link to={`/listas/${list.slug}`} className="font-bold text-primary hover:underline">Ver lista completa</Link>
  </article>
);

export default EditorialListCard;
