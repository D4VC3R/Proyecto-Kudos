import React from 'react';
import { Link } from 'react-router-dom';
import { Star } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
import { buildItemUrl } from '../../lib/search.js';

// Reseña reciente de un usuario en su perfil público (F3-03).
const PublicReviewCard = ({ review }) => {
  const itemUrl = buildItemUrl(review.item);

  return (
    <div className="flex flex-col gap-2 rounded-xl border border-slate-100 bg-background p-4">
      <div className="flex flex-wrap items-center gap-2">
        {itemUrl ? (
          <Link to={itemUrl} className="break-words font-bold text-text-highlight hover:underline">
            {review.item.name}
          </Link>
        ) : (
          <span className="break-words font-bold text-text-highlight">{review.item.name}</span>
        )}
        <span className="flex shrink-0 items-center gap-1 rounded-full bg-yellow-50 px-2 py-0.5 text-[11px] font-bold text-yellow-800 ring-1 ring-yellow-300">
          <Star size={12} className="text-yellow-600 fill-yellow-500" />
          Reseña{review.score != null ? ` · ${review.score}` : ''}
        </span>
      </div>
      <p className="whitespace-pre-line text-sm text-slate-700">{review.content}</p>
      <span className="text-xs text-slate-400">{formatDate(review.created_at)}</span>
    </div>
  );
};

export default PublicReviewCard;
