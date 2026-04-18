import React from 'react';

export const UserRankingHeader = () => {
  return (
    <div className="text-center">
      <h1 className="text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">
        Ranking <span className="text-blue-600 drop-shadow-sm">Global</span>
      </h1>
      <p className="mt-4 text-lg font-medium text-slate-500 max-w-2xl mx-auto">
        Descubre a los mejores valoradores de la plataforma. Acumula Kudos para escalar en la tabla.
      </p>
    </div>
  );
};

