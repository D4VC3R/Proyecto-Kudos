import React from 'react';
import { Info } from 'lucide-react';
import AttributeChips from './AttributeChips.jsx';
import ItemFacts from './ItemFacts.jsx';
import ItemPlaceMap from './ItemPlaceMap.jsx';
import ScoreStamp from '../ui/ScoreStamp.jsx';
import VoteDistribution from './VoteDistribution.jsx';
import SectionHead from '../ui/SectionHead.jsx';
import CommentBox from '../comments/CommentBox.jsx';
import AddToListButton from '../lists/AddToListButton.jsx';
import { hasText } from '../../lib/text.js';
import { getAttributeEntries } from '../../lib/attributes.js';
import { getPlaceCoordinates, getPlaceMapZoom } from '../../lib/places.js';
import { VOTE_TYPE_BADGES } from '../../lib/constants.js';
import { formatScore, formatVotes } from '../../lib/formatters.js';

const PILL = 'inline-flex items-center gap-2 rounded-full border border-line px-3 py-1 text-small text-ink';

const ItemDetailColumn = ({ item }) => {
  // Los ítems sin descripción (p. ej. importados) no muestran la sección "Acerca de".
  const description = hasText(item.description) ? item.description.trim() : null;
  // Mapa solo con coordenadas válidas (destinos); el resto de categorías no las tienen.
  const coordinates = getPlaceCoordinates(item.attributes);
  // Estado del usuario con este ítem: nota, wishlist o "no me interesa". Sin sesión no llega user_vote.
  const userVote = item.user_vote;
  const userVoteBadge = userVote ? (VOTE_TYPE_BADGES[userVote.type] ?? null) : null;
  const userVoteText = userVote?.type === 'vote' && userVote.score != null ? `Tu nota: ${userVote.score}` : userVoteBadge?.label;
  const score = formatScore(item.vote_avg, item.vote_count);

  const year = item.attributes?.year ?? item.released_at?.slice(0, 4);
  const schema = item.category?.attribute_schema;
  // Lo que ya sale como chips (los atributos de la tarjeta) no se repite en la cuadrícula de datos.
  const chipKeys = new Set(getAttributeEntries(schema, item.attributes, { cardOnly: true }).map((entry) => entry.key));
  const facts = getAttributeEntries(schema, item.attributes).filter((entry) => !chipKeys.has(entry.key));
  const hasOriginalTitle = hasText(item.original_title) && item.original_title.trim() !== item.name?.trim();

  return (
    <article aria-labelledby="item-title" className="min-w-0">
      <p className="text-caption font-semibold uppercase tracking-widest text-kicker">
        {item.category?.name}{year ? ` · ${year}` : ''}
      </p>
      <h1 id="item-title" className="mb-1.5 mt-2 font-display text-h1 text-ink">{item.name}</h1>
      {hasOriginalTitle && (
        <p className="text-small text-ink-muted">
          Título original: <i className="font-display not-italic">{item.original_title.trim()}</i>
        </p>
      )}

      <AttributeChips schema={schema} attributes={item.attributes} />
      <ItemFacts entries={facts} />

      <div className="my-6 flex items-center gap-6">
        <ScoreStamp score={score} size="lg" />
        <div className="min-w-0 flex-1">
          <div className="flex items-baseline justify-between gap-3 text-small">
            <p className="font-semibold">Nota de la comunidad</p>
            <p className="tabular-nums text-ink-muted">{formatVotes(item.vote_count)}</p>
          </div>
          <VoteDistribution distribution={item.vote_distribution} />
        </div>
      </div>

      {(userVoteBadge || item.creator) && (
        <div className="flex flex-wrap items-center gap-3">
          {userVoteBadge && (
            <span className={PILL}>
              <userVoteBadge.icon size={16} aria-hidden="true" />
              {userVoteText}
            </span>
          )}
          {item.creator && (
            <span className={PILL}>
              <Info size={16} aria-hidden="true" />
              Aportado por <strong>{item.creator.name}</strong>
            </span>
          )}
        </div>
      )}

      <div className="mt-6 flex flex-wrap gap-2">
        <AddToListButton item={item} variant="detail" />
      </div>

      {description && (
        <section aria-labelledby="item-about" className="mt-10 flex flex-col gap-4">
          <SectionHead id="item-about" title="Acerca de" />
          <p className="max-w-prose whitespace-pre-line text-body leading-relaxed text-ink">{description}</p>
        </section>
      )}

      {coordinates && (
        <ItemPlaceMap
          name={item.name}
          coordinates={coordinates}
          zoom={getPlaceMapZoom(item.attributes)}
          className="mt-10"
        />
      )}

      <CommentBox itemId={item.id} userVote={item.user_vote} className="mt-10" />
    </article>
  );
};

export default ItemDetailColumn;
