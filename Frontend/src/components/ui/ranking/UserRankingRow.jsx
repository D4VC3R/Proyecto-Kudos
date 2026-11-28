import React from 'react';
import clsx from 'clsx';
import KudosBadge from '../KudosBadge.jsx';

// Fila del ranking de usuarios (portada y /ranking): el primero se agranda y el usuario, si sale, va resaltado.
const UserRankingRow = ({ user }) => {
  const first = user.rank === 1;

  return (
    <li className={clsx(
      'flex items-center gap-3.5 border-t border-line py-3.5 first:border-t-0',
      user.isMe && 'rounded-md bg-line/40 px-2.5',
    )}>
      <span
        aria-hidden="true"
        className={clsx(
          'grid shrink-0 place-items-center rounded-full border font-display font-semibold tabular-nums',
          first ? 'h-16 w-16 border-on-kudos bg-kudos text-3xl text-on-kudos' : 'h-11 w-11 border-ink text-xl',
        )}
      >
        {user.rank}
      </span>
      <span className={clsx('min-w-0 flex-1 truncate font-display font-semibold leading-tight', first ? 'text-2xl' : 'text-lg')}>
        <span className="sr-only">Puesto {user.rank}: </span>
        {user.name}
        {user.isMe && <span className="ml-1.5 font-sans text-caption font-semibold text-ink-muted">(tú)</span>}
      </span>
      <KudosBadge value={user.total_kudos} />
    </li>
  );
};

export default UserRankingRow;
