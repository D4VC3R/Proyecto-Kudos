import React, { useState } from 'react';
import { Star } from 'lucide-react';
import clsx from 'clsx';

export const VoteStars = ({ onVote, isPending }) => {
  const [hoverScore, setHoverScore] = useState(0);

  const getScoreFromEvent = (clientX, currentTarget) => {
    const rect = currentTarget.getBoundingClientRect();
    const x = clientX - rect.left;
    const width = rect.width;

    let calculatedScore = Math.ceil((x / width) * 20) / 2;
    if (calculatedScore < 0.5) calculatedScore = 0.5;
    if (calculatedScore > 10) calculatedScore = 10;
    return calculatedScore;
  };

  const handlePointerMove = (e) => {
    if (isPending) return;
    setHoverScore(getScoreFromEvent(e.clientX, e.currentTarget));
  };

  const handlePointerLeave = () => {
    if (isPending) return;
    setHoverScore(0);
  };

  const handleClick = (e) => {
    if (isPending) return;

    const finalScore = getScoreFromEvent(e.clientX, e.currentTarget);
    onVote(finalScore);
  };

  return (
    <div className="flex flex-col items-center mb-6">
      <h4 className="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
        Tu puntuación: <span className={clsx("ml-2 text-xl font-black", hoverScore > 0 ? "text-primary" : "text-slate-300")}>{hoverScore > 0 ? hoverScore.toFixed(1) : '-.-'}</span>
      </h4>

      <div
        className={clsx(
          "flex gap-1 sm:gap-2 cursor-pointer touch-none select-none transition-opacity",
          isPending && "opacity-50 pointer-events-none"
        )}
        onPointerMove={handlePointerMove}
        onPointerLeave={handlePointerLeave}
        onClick={handleClick}
      >
        {[...Array(10)].map((_, i) => {
          const starValue = i + 1;

          let fillPercent = 0;
          if (hoverScore >= starValue) {
            fillPercent = 100;
          } else if (hoverScore + 0.5 === starValue) {
            fillPercent = 50;
          }

          const starSizeClasses = "w-6 h-6 min-[400px]:w-7 min-[400px]:h-7 sm:w-8 sm:h-8 md:w-10 md:h-10";

          return (
            <div key={i} className="relative group">

              <Star
                className={clsx(starSizeClasses, "text-accent drop-shadow-sm transition-transform group-hover:scale-110")}
                strokeWidth={1.5}
                fill="white"
              />

              <div
                className="absolute top-0 left-0 overflow-hidden pointer-events-none h-full"
                style={{ width: `${fillPercent}%` }}
              >
                <Star
                  className={clsx(starSizeClasses, "text-accent fill-accent drop-shadow-[0_0_8px_rgba(250,204,21,0.6)] transition-transform group-hover:scale-110")}
                  strokeWidth={1.5}
                />
              </div>
            </div>
          );
        })}
      </div>

      <p className="text-xs text-slate-400 mt-4 font-medium">Haz clic para confirmar tu voto</p>
    </div>
  );
};