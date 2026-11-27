import React from 'react';
import { Star } from 'lucide-react';
import { Link } from 'react-router-dom';
import  BouncyCard  from '../animations/BouncyCard';
import StorageImage from "../ui/StorageImage.jsx";
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import AttributeChips from './AttributeChips.jsx';
import AddToListButton from '../lists/AddToListButton.jsx';
import { buildItemUrl } from '../../lib/search.js';
import { formatScore } from '../../lib/formatters.js';

const ItemCard = ({ item }) => {
  const displayScore = formatScore(item.vote_avg, item.vote_count) ?? 'Sin nota';
  const imagePath = item.images?.[0]?.variants?.thumb;

  const card = (
    <BouncyCard
      tabIndex={-1}
      className="group relative w-full aspect-[3/4] overflow-hidden rounded-3xl bg-surface shadow-lg ring-1 ring-slate-200 transition-shadow hover:shadow-xl"
    >
      <div className="absolute inset-0">
        <StorageImage
          src={imagePath}
          alt={item.name}
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
          fallback={
            <ItemPlaceholder
              name={item.name}
              categorySlug={item.category?.slug}
              categoryIcon={item.category?.icon}
              variant="card"
              className="h-full w-full transition-transform duration-500 group-hover:scale-110"
            />
          }
        />
        <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent" />
      </div>

      <div className="absolute left-3 top-3">
        <AddToListButton item={item} variant="card" />
      </div>

      <div className="absolute right-3 top-3">
        <div className="flex items-center gap-1 rounded-full bg-kudos px-2 py-1 font-bold text-yellow-950 shadow-md ring-2 ring-yellow-300">
          <Star size={14} className="fill-yellow-600 stroke-yellow-700" />
          <span className="text-xs">{displayScore}</span>
        </div>
      </div>

      <div className="absolute inset-x-0 bottom-0 flex flex-col gap-2 p-4">
        <h3 className="line-clamp-2 text-lg font-black tracking-tight text-text-btn drop-shadow-md">
          {item.name}
        </h3>
        <AttributeChips schema={item.category?.attribute_schema} attributes={item.attributes} variant="card" />
      </div>
    </BouncyCard>
  );

  // `whileTap` hace tabulable el artículo: se quita para que el enlace sea la única parada de Tab.
  // Es un enlace real para el teclado, la pestaña nueva y los buscadores; sin categoría no hay URL.
  const url = buildItemUrl(item);
  if (!url) return card;

  return (
    <Link to={url} className="block rounded-3xl focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary">
      {card}
    </Link>
  );
};

export default ItemCard;