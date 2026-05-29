import React from 'react';
import clsx from 'clsx';
import StarItem from './StarItem';

import { usePointerScore } from '../../hooks/common/usePointerScore.js';

const VoteStars = ({ onVote, isPending }) => {
  const { hoverScore, events } = usePointerScore(onVote, isPending);

  return (
    <div className="flex flex-col items-center mb-6">
      <h4 className="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
        Tu puntuación:
        <span className={clsx("ml-2 text-xl font-black", hoverScore > 0 ? "text-primary" : "text-slate-300")}>
          {hoverScore > 0 ? hoverScore.toFixed(1) : '-.-'}
        </span>
      </h4>

      <div
        className={clsx(
          "flex gap-1 sm:gap-2 cursor-pointer touch-none select-none transition-opacity",
          isPending && "opacity-50 pointer-events-none"
        )}
        {...events}
      >
        {Array.from({ length: 10 }).map((_, i) => {
          const starValue = i + 1;
          const fillPercent = hoverScore >= starValue ? 100 : (hoverScore + 0.5 === starValue ? 50 : 0);

          return <StarItem key={i} fillPercent={fillPercent} />;
        })}
      </div>

      <p className="text-xs text-slate-400 mt-4 font-medium">Haz clic para confirmar tu voto</p>
    </div>
  );
};

export default VoteStars;