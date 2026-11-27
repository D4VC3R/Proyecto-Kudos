import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import StorageImage from '../ui/StorageImage.jsx';
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import ScoreStamp from '../ui/ScoreStamp.jsx';
import AttributeChips from './AttributeChips.jsx';
import AddToListButton from '../lists/AddToListButton.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';
import { startPosterTransition } from '../../hooks/common/posterTransition.js';
import { buildItemUrl } from '../../lib/search.js';
import { formatScore } from '../../lib/formatters.js';

// A partir de la octava, las tarjetas suben a la vez para no hacer esperar.
const MAX_CASCADE_STEP = 7;

const ItemCard = ({ item, index = 0 }) => {
  const navigate = useNavigate();
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
        <Link to={url} onClick={(event) => startPosterTransition(event, navigate, url)} className={`group block rounded-md ${FOCUS_RING}`}>
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
