import React from 'react';
import PodiumItem from './PodiumItem.jsx';
import Button from '../Button.jsx';
import { buildItemUrl } from '../../../lib/search.js';

// Ranking de una categoría como banda (la de la portada). No es una caja con scroll interno, para no
// anidar desplazamientos: crece con «Ver más».
const Ranking = ({ title, items = [], categorySlug, hasNextPage, isFetchingNextPage, fetchNextPage }) => (
  <section
    aria-labelledby="category-ranking"
    data-ranking-band
    data-title-top
    className="home-ranking relative grid grid-cols-1 gap-6 overflow-hidden rounded-md bg-band px-5 pb-7 pt-8 text-ink md:grid-cols-band md:items-start md:gap-10 md:px-10 md:pb-8 md:pt-10"
  >
    <div>
      <p className="text-caption font-semibold uppercase tracking-widest text-kicker">Ranking</p>
      <h2 id="category-ranking" className="font-display text-h2">Top {title}</h2>
      <p className="mt-2 max-w-md text-ink-muted">Ordenado por la nota media de quienes votan.</p>
    </div>
    <div>
      {items.length === 0 ? (
        <p className="text-ink-muted">Todavía no hay ítems en el ranking.</p>
      ) : (
        <ol>
          {items.map((item, index) => (
            <PodiumItem
              key={`${item.id}-${index}`}
              item={item}
              rank={index + 1}
              // Los ítems del ranking no traen `category`, así que el slug llega aparte.
              to={buildItemUrl({ id: item.id, category: { slug: categorySlug } })}
            />
          ))}
        </ol>
      )}
      {hasNextPage && (
        <Button variant="link" onClick={() => fetchNextPage()} disabled={isFetchingNextPage} className="mt-4">
          {isFetchingNextPage ? 'Cargando…' : 'Ver más'}
        </Button>
      )}
    </div>
  </section>
);

export default Ranking;
