import React from 'react';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
import { Star, X } from 'lucide-react';
import { formatDate, resolveCategoryIcon } from '../../lib/formatters';
import { buildItemUrl } from '../../lib/search.js';
import Button from '../ui/Button.jsx';
import StorageImage from "../ui/StorageImage.jsx";
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';

// Tarjeta de un ítem de la wishlist: solo pinta; las acciones llegan por props.
const WishlistItemCard = ({ vote, onRateClick, onRemoveClick, isRemoving, isBusy }) => {
  const item = vote.item;
  const imageUrl = item.images?.[0]?.variants?.thumb || item.images?.[0]?.variants?.banner;
  const itemUrl = buildItemUrl(item);
  const CategoryIcon = resolveCategoryIcon(item.category);

  return (
    <article data-wishlist-card className="flex flex-col gap-4 rounded-md border border-line bg-surface p-4 sm:flex-row sm:items-center">
      <div className="flex min-w-0 flex-1 items-start gap-4">
        <span className="block w-12 shrink-0 overflow-hidden rounded-sm border border-line bg-surface aspect-poster">
          <StorageImage
            src={imageUrl}
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

        <div className="min-w-0 flex-1">
          <h3 className="font-display text-h3 text-ink line-clamp-2">
            {itemUrl ? (
              <Link to={itemUrl} className={clsx('rounded-sm underline-offset-3 hover:underline', FOCUS_RING)}>{item.name}</Link>
            ) : (
              item.name
            )}
          </h3>

          <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-small text-ink-muted">
            {item.category?.name && (
              <span className="inline-flex items-center gap-1 text-ink">
                <CategoryIcon size={14} aria-hidden="true" />
                {item.category.name}
              </span>
            )}
            {item.released_at && <span>Lanzamiento: {formatDate(item.released_at)}</span>}
            <span>Añadido el {formatDate(vote.voted_at)}</span>
          </p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2 sm:shrink-0">
        <Button
          variant="primary"
          icon={Star}
          onClick={() => onRateClick(vote)}
          disabled={isBusy}
          aria-label={`Ya lo conozco: valorar «${item.name}»`}
        >
          Ya lo conozco
        </Button>
        <Button
          variant="ghost"
          icon={X}
          className="text-danger"
          onClick={() => onRemoveClick(vote)}
          isLoading={isRemoving}
          disabled={isBusy}
          aria-label={`Quitar «${item.name}» de la wishlist`}
        >
          Quitar
        </Button>
      </div>
    </article>
  );
};

export default WishlistItemCard;
