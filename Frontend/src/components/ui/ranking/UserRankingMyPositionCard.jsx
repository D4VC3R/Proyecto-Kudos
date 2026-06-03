import React from 'react';

const UserRankingMyPositionCard = ({ position }) => {
  if (!position) return null;

  return (
    <div className="mx-auto flex w-full max-w-md items-center justify-between rounded-2xl bg-primary p-6 border border-blue-100 shadow-sm">
      <div>
        <h3 className="font-bold text-3xl text-surface">Tu posición: </h3>
      </div>
      <div className="text-right">
        <div className="text-3xl font-black text-accent">#{position.rank}</div>
        <div className="text-sm font-bold text-surface">{position.total_kudos} K</div>
      </div>
    </div>
  );
};

export default UserRankingMyPositionCard;