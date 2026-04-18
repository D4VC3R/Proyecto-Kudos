import React from 'react';

export const UserRankingMyPositionCard = ({ position }) => {
  if (!position) return null;

  return (
    <div className="mx-auto flex w-full max-w-md items-center justify-between rounded-2xl bg-blue-50 p-6 border border-blue-100 shadow-sm">
      <div>
        <h3 className="text-lg font-bold text-blue-900">Tu posición</h3>
        <p className="text-blue-700 text-sm font-medium">Sigue votando para subir</p>
      </div>
      <div className="text-right">
        <div className="text-3xl font-black text-blue-600">#{position.rank}</div>
        <div className="text-sm font-bold text-blue-500">{position.total_kudos} K</div>
      </div>
    </div>
  );
};

