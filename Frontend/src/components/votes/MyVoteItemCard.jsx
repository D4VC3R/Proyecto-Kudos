import React from 'react';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
import { Trash2 } from 'lucide-react';
import { formatDate, resolveCategoryIcon } from '../../lib/formatters';
import { buildItemUrl } from '../../lib/search.js';
import Button from '../ui/Button.jsx';
import Chip from '../ui/Chip.jsx';
import StorageImage from "../ui/StorageImage.jsx";
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';
import { VOTE_TYPE_BADGES } from '../../lib/constants.js';

// Tarjeta de un voto del historial: solo pinta; el borrado llega por props.
const MyVoteItemCard = ({ vote, isDeleting, onDeleteClick }) => {
  const badge = VOTE_TYPE_BADGES[vote.type] ?? VOTE_TYPE_BADGES.vote;
  const isRated = vote.type === 'vote';
  const item = vote.item;
  const name = item?.name || 'Ítem desconocido';
  const imageUrl = item?.images?.[0]?.variants?.thumb || item?.images?.[0]?.variants?.banner;
  const itemUrl = item ? buildItemUrl(item) : null;
  const CategoryIcon = resolveCategoryIcon(item?.category);

  return (
    <article data-my-vote-card className="flex flex-col gap-4 rounded-md border border-line bg-surface p-4 sm:flex-row sm:items-center">
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
                name={item?.name}
                categorySlug={item?.category?.slug}
                categoryIcon={item?.category?.icon}
                variant="thumb"
                decorative
                className="h-full w-full"
              />
            }
          />
        </span>

        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="font-display text-h3 text-ink line-clamp-2">
              {itemUrl ? (
                <Link to={itemUrl} className={clsx('rounded-sm underline-offset-3 hover:underline', FOCUS_RING)}>{name}</Link>
              ) : (
                name
              )}
            </h3>
            <Chip>{badge.label}</Chip>
          </div>

          <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-small text-ink-muted">
            {item?.category?.name && (
              <span className="inline-flex items-center gap-1 text-ink">
                <CategoryIcon size={14} aria-hidden="true" />
                {item.category.name}
              </span>
            )}
            <time dateTime={vote.voted_at}>{formatDate(vote.voted_at)}</time>
          </p>

          {isRated && (
            <p className="mt-2 text-small text-ink">
              Tu nota: <span className="font-display font-semibold tabular-nums text-stamp">{vote.score}</span> de 10
            </p>
          )}
        </div>
      </div>

      <div className="flex flex-wrap gap-2 sm:shrink-0">
        <Button
          variant="ghost"
          icon={Trash2}
          className="text-danger"
          onClick={() => onDeleteClick(vote)}
          isLoading={isDeleting}
          aria-label={`Eliminar el voto de «${name}»`}
        >
          Eliminar
        </Button>
      </div>
    </article>
  );
};

export default MyVoteItemCard;
