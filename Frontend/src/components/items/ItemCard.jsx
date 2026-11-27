import React from 'react';
import { Link } from 'react-router-dom';
import StorageImage from '../ui/StorageImage.jsx';
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import ScoreStamp from '../ui/ScoreStamp.jsx';
import AttributeChips from './AttributeChips.jsx';
import AddToListButton from '../lists/AddToListButton.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';
import { buildItemUrl } from '../../lib/search.js';
import { formatScore } from '../../lib/formatters.js';

// A partir de la octava, las tarjetas suben a la vez para no hacer esperar.
const MAX_CASCADE_STEP = 7;

// Solo el póster pulsado lleva el nombre de transición, porque dos elementos con el mismo nombre anulan la
// transición, y la ficha lo recibe como el mismo elemento.
const handlePosterTransition = (event) => {
  document.querySelectorAll('[data-card-poster]').forEach((el) => {
    el.style.viewTransitionName = '';
  });
  if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
  event.currentTarget.querySelector('[data-card-poster]')?.style.setProperty('view-transition-name', 'poster');
};

const ItemCard = ({ item, index = 0 }) => {
  const imagePath = item.images?.[0]?.variants?.thumb;

  const body = (
    <>
      <span
        data-card-poster
        className="block aspect-poster overflow-hidden rounded-md border border-line bg-surface transition duration-250 ease-soft group-hover:-translate-y-1 group-hover:shadow-glow"
      >
        <StorageImage
          src={imagePath}
          alt=""
          width={400}
          height={600}
          className="h-full w-full object-cover"
          fallback={
            <ItemPlaceholder
              name={item.name}
              categorySlug={item.category?.slug}
              categoryIcon={item.category?.icon}
              variant="card"
              decorative
              className="h-full w-full"
            />
          }
        />
      </span>
      <span className="mb-1 mt-2.5 line-clamp-2 text-small font-semibold leading-snug text-ink underline-offset-3 group-hover:underline">
        {item.name}
      </span>
    </>
  );

  // Es un enlace real para el teclado, la pestaña nueva y los buscadores; sin categoría no hay URL.
  const url = buildItemUrl(item);

  return (
    <article data-item-card className="relative min-w-0 animate-rise" style={{ '--i': Math.min(index, MAX_CASCADE_STEP) }}>
      {url ? (
        <Link to={url} onClick={handlePosterTransition} className={`group block rounded-md ${FOCUS_RING}`}>
          {body}
        </Link>
      ) : (
        <div>{body}</div>
      )}
      <AttributeChips variant="card" category={item.category} schema={item.category?.attribute_schema} attributes={item.attributes} />
      <div className="mt-2 flex items-center justify-between gap-2">
        <ScoreStamp score={formatScore(item.vote_avg, item.vote_count)} />
        <AddToListButton item={item} />
      </div>
    </article>
  );
};

export default ItemCard;
