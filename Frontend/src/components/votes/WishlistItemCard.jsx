import React from 'react';
import { Link } from 'react-router-dom';
import { Star, X } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
import { buildItemUrl } from '../../lib/search.js';
import Button from '../ui/Button.jsx';
import StorageImage from "../ui/StorageImage.jsx";
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';

// Tarjeta de un ítem de la wishlist (F2-05): solo pinta; las acciones llegan por props.
const WishlistItemCard = ({ vote, onRateClick, onRemoveClick, isRemoving, isBusy }) => {
  const item = vote.item;
  const imageUrl = item.images?.[0]?.variants?.thumb || item.images?.[0]?.variants?.banner;
  const itemUrl = buildItemUrl(item);

  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-surface p-5 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
      <div className="flex items-start gap-4 flex-1">
        <div className="mt-1 flex h-16 w-14 shrink-0 items-center justify-center rounded-xl overflow-hidden shadow-inner bg-slate-100">
          <StorageImage
            src={imageUrl}
            alt={item.name || 'Ítem'}
            className="h-full w-full"
            fallback={
              <ItemPlaceholder
                name={item.name}
                categorySlug={item.category?.slug}
                categoryIcon={item.category?.icon}
                variant="thumb"
                className="h-full w-full"
              />
            }
          />
        </div>

        <div className="flex flex-col w-full">
          <h4 className="text-lg font-bold text-text-highlight line-clamp-1">
            {itemUrl ? (
              <Link to={itemUrl} className="hover:text-primary transition-colors">{item.name}</Link>
            ) : (
              item.name
            )}
          </h4>

          <div className="flex items-center gap-x-3 gap-y-1 mt-1 text-sm font-medium text-text-normal flex-wrap">
            {item.category?.name && <span className="text-primary">{item.category.name}</span>}
            {item.released_at && <span>Lanzamiento: {formatDate(item.released_at)}</span>}
            <span>Añadido el {formatDate(vote.voted_at)}</span>
          </div>
        </div>
      </div>

      <div className="flex flex-wrap gap-2 sm:self-center">
        <Button
          title="Ya lo he visto o jugado: valorar"
          variant="solid"
          color="primary"
          size="sm"
          icon={Star}
          onClick={() => onRateClick(vote)}
          disabled={isBusy}
        >
          Ya lo conozco
        </Button>
        <Button
          title="Quitar de la wishlist (no me interesa)"
          variant="ghost"
          color="danger"
          size="sm"
          icon={X}
          onClick={() => onRemoveClick(vote)}
          isLoading={isRemoving}
          disabled={isBusy}
        >
          Quitar
        </Button>
      </div>
    </div>
  );
};

export default WishlistItemCard;
