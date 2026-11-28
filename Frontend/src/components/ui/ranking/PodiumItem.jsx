import React from 'react';
import clsx from 'clsx';
import { Link } from 'react-router-dom';
import ScoreStamp from '../ScoreStamp.jsx';
import { formatScore } from '../../../lib/formatters.js';

// Fila del ranking de una categoría: el primero, en dorado y más grande. Toda la fila es el enlace a la ficha.
const PodiumItem = ({ item, rank, to }) => {
  const first = rank === 1;

  return (
    <li className="border-t border-line first:border-t-0">
      <Link
        to={to}
        className="group flex items-center gap-3.5 rounded-md py-3.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
      >
        <span
          aria-hidden="true"
          className={clsx(
            'grid shrink-0 place-items-center rounded-full border font-display font-semibold tabular-nums',
            first ? 'h-16 w-16 border-on-kudos bg-kudos text-3xl text-on-kudos' : 'h-11 w-11 border-ink text-xl',
          )}
        >
          {rank}
        </span>
        <span
          className={clsx(
            'min-w-0 flex-1 line-clamp-2 font-display font-semibold leading-tight underline-offset-3 group-hover:underline',
            first ? 'text-2xl' : 'text-lg',
          )}
        >
          <span className="sr-only">Puesto {rank}: </span>
          {item.name}
        </span>
        <ScoreStamp score={formatScore(item.vote_avg, item.vote_count)} />
      </Link>
    </li>
  );
};

export default PodiumItem;
