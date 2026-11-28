import React from 'react';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
import { Star } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
import { buildItemUrl } from '../../lib/search.js';
import Card from '../ui/Card.jsx';
import Chip from '../ui/Chip.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';

// Reseña reciente de un usuario en su perfil público.
const PublicReviewCard = ({ review }) => {
  const itemUrl = buildItemUrl(review.item);

  return (
    <Card as="li" className="flex flex-col gap-2">
      <div className="flex flex-wrap items-center gap-2">
        <h3 className="break-words font-display text-h3 text-ink">
          {itemUrl ? (
            <Link to={itemUrl} className={clsx('rounded-sm underline-offset-3 hover:underline', FOCUS_RING)}>
              {review.item.name}
            </Link>
          ) : (
            review.item.name
          )}
        </h3>
        <Chip>
          <Star size={12} aria-hidden="true" className="mr-1 text-kudos" />
          Reseña{review.score != null ? ` · ${review.score}` : ''}
        </Chip>
      </div>
      <p className="whitespace-pre-line text-small text-ink">{review.content}</p>
      <time dateTime={review.created_at} className="text-caption text-ink-muted">{formatDate(review.created_at)}</time>
    </Card>
  );
};

export default PublicReviewCard;
