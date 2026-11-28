import React from 'react';
import { Link } from 'react-router-dom';
import Button from '../ui/Button.jsx';
import KudosBadge from '../ui/KudosBadge.jsx';
import UserRankingRow from '../ui/ranking/UserRankingRow.jsx';

// Banda del ranking global de la portada: texto con la posición del usuario y podio. Sin ranking no pinta nada.
const HomeRankingBand = ({ ranking, isAuthenticated }) => {
  if (!ranking?.podium?.length) return null;
  const { podium, me, myKudos, kudosToNext } = ranking;
  // La caché del ranking se vacía al entrar y salir, pero la posición solo cuenta con sesión.
  const myRank = isAuthenticated ? ranking.myRank : null;

  return (
    <section
      aria-labelledby="home-ranking"
      data-ranking-band
      className="home-ranking relative grid grid-cols-1 gap-6 overflow-hidden rounded-md bg-band px-5 pb-7 pt-8 text-ink md:grid-cols-band md:items-center md:gap-10 md:px-10 md:pb-8 md:pt-10"
    >
      <div>
        <p className="text-caption font-semibold uppercase tracking-widest text-kicker">Ranking</p>
        <h2 id="home-ranking" className="font-display text-h2">Quién suma más kudos</h2>
        <p className="mt-2 max-w-md text-ink-muted">
          Votar, entrar cada día y conseguir que acepten tus propuestas suma kudos.
          {myRank && ` Vas el #${myRank}`}
          {myRank && (kudosToNext ? `: te faltan ${kudosToNext} para el #${myRank - 1}.` : '.')}
        </p>
        <div className="mt-4 flex flex-wrap items-center gap-3">
          {myRank && <KudosBadge value={myKudos} size="lg" />}
          <Button as={Link} to="/ranking" variant="link">Ver ranking completo</Button>
        </div>
      </div>
      <ol>
        {podium.map((user) => <UserRankingRow key={user.id} user={user} />)}
        {me && <UserRankingRow user={me} />}
      </ol>
    </section>
  );
};

export default HomeRankingBand;
