import React from 'react';
import Pagination from '../Pagination.jsx';
import UserRankingMyPositionCard from './UserRankingMyPositionCard.jsx';
import UserRankingRow from './UserRankingRow.jsx';

const UserRankingTable = ({ items = [], myPosition = null, meta, onPageChange }) => (
  <section
    aria-labelledby="user-ranking"
    data-ranking-band
    data-title-top
    className="home-ranking relative grid grid-cols-1 gap-6 overflow-hidden rounded-md bg-band px-5 pb-7 pt-8 text-ink md:grid-cols-band md:items-start md:gap-10 md:px-10 md:pb-8 md:pt-10"
  >
    <div>
      <p className="text-caption font-semibold uppercase tracking-widest text-kicker">Usuarios</p>
      <h2 id="user-ranking" className="font-display text-h2">Quién suma más kudos</h2>
      <p className="mt-2 max-w-md text-ink-muted">Ordenado por kudos; con empate, va antes quien tiene la cuenta más antigua.</p>
      <UserRankingMyPositionCard position={myPosition} />
    </div>
    <div>
      {items.length === 0 ? (
        <p className="text-ink-muted">Todavía no hay nadie en el ranking.</p>
      ) : (
        <ol>
          {items.map((user) => (
            <UserRankingRow key={user.id} user={{ ...user, isMe: user.id === myPosition?.user_id }} />
          ))}
        </ol>
      )}
      <Pagination meta={meta} onPageChange={onPageChange} />
    </div>
  </section>
);

export default UserRankingTable;
