import React from 'react';
import KudosBadge from '../KudosBadge.jsx';

const UserRankingMyPositionCard = ({ position }) => {
  if (!position) return null;

  return (
    <div data-my-position className="mt-6 flex items-center justify-between gap-4 border-t border-line pt-4">
      <p className="font-display text-lg font-semibold">Tu posición</p>
      <div className="flex items-center gap-3">
        <span className="font-display text-3xl font-semibold tabular-nums">#{position.rank}</span>
        <KudosBadge value={position.total_kudos} size="lg" />
      </div>
    </div>
  );
};

export default UserRankingMyPositionCard;
