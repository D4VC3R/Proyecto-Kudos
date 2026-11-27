import React from 'react';
import { Link } from 'react-router-dom';
import Card from '../ui/Card.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import { hasText } from '../../lib/text.js';
import { resolveCategoryIcon } from '../../lib/formatters.js';

// Pósters del collage de la tarjeta.
const COLLAGE_SIZE = 4;

// Tarjeta de una lista en la portada: un solo enlace con el collage de sus primeros ítems, título, descripción y meta.
// Todo son `span` porque dentro de un `<a>` no puede haber bloques de verdad.
const EditorialListCard = ({ list }) => {
  const items = (list.items ?? []).map((entry) => entry.item).filter(Boolean);
  const count = list.items_count ?? items.length;
  const categories = [...new Map(items.filter((item) => item.category).map((item) => [item.category.slug, item.category])).values()];

  return (
    <Card as={Link} to={`/listas/${list.slug}`} variant="interactive" data-list-card className="grid gap-3">
      <span className="grid grid-cols-4 gap-1.5">
        {items.slice(0, COLLAGE_SIZE).map((item) => (
          <span key={item.id} className="block aspect-poster overflow-hidden rounded border border-line bg-surface">
            <StorageImage
              src={item.images?.[0]?.variants?.thumb}
              alt=""
              width={200}
              height={300}
              className="h-full w-full object-cover"
              fallback={
                <ItemPlaceholder
                  name={item.name}
                  categorySlug={item.category?.slug}
                  categoryIcon={item.category?.icon}
                  variant="thumb"
                  decorative
                  className="h-full w-full"
                />
              }
            />
          </span>
        ))}
      </span>
      <span className="block font-display text-xl font-semibold leading-tight tracking-tight text-ink">{list.title}</span>
      {hasText(list.description) && (
        <span className="line-clamp-2 text-small text-ink-muted">{list.description}</span>
      )}
      <span className="flex flex-wrap items-center gap-2 text-small text-ink-muted">
        <span>
          {list.author ? `Lista de @${list.author.username}` : 'Lista editorial'}
          {' · '}
          <span className="tabular-nums">{count}</span> {count === 1 ? 'elemento' : 'elementos'}
        </span>
        {categories.map((category) => {
          const Icon = resolveCategoryIcon(category);
          return <Icon key={category.slug} size={16} className="text-cat-icon" aria-hidden="true" />;
        })}
        {categories.length > 0 && (
          <span className="sr-only">Incluye: {categories.map((category) => category.name).join(', ')}</span>
        )}
      </span>
    </Card>
  );
};

export default EditorialListCard;
