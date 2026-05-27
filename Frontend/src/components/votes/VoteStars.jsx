import React, { useState } from 'react';
import { Star } from 'lucide-react';
import clsx from 'clsx';

export const VoteStars = ({ onVote, isPending }) => {
  const [hoverScore, setHoverScore] = useState(0);

  const handleMouseMove = (e) => {
    if (isPending) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const width = rect.width;

    let calculatedScore = Math.ceil((x / width) * 20) / 2;
    if (calculatedScore < 0.5) calculatedScore = 0.5;
    if (calculatedScore > 10) calculatedScore = 10;
    
    setHoverScore(calculatedScore);
  };

  const handleMouseLeave = () => {
    if (isPending) return;
    setHoverScore(0);
  };

  const handleClick = () => {
    if (isPending || hoverScore === 0) return;
    onVote(hoverScore);
  };

  return (
    <div className="flex flex-col items-center mb-6">
      <h4 className="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
        Tu puntuación: <span className={clsx("ml-2 text-xl font-black", hoverScore > 0 ? "text-primary" : "text-slate-300")}>{hoverScore > 0 ? hoverScore.toFixed(1) : '-.-'}</span>
      </h4>
      
      <div 
        className={clsx(
          "flex gap-1 md:gap-2 cursor-pointer touch-none select-none transition-opacity",
          isPending && "opacity-50 pointer-events-none"
        )}
        onMouseMove={handleMouseMove}
        onMouseLeave={handleMouseLeave}
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

          return (
            <div key={i} className="relative">

              <Star 
                size={40} 
                className="text-accent drop-shadow-sm transition-transform hover:scale-110"
                strokeWidth={1.5}
                fill="white"
              />

              <div 
                className="absolute top-0 left-0 overflow-hidden pointer-events-none" 
                style={{ width: `${fillPercent}%` }}
              >
                <Star 
                  size={40} 
                  className="text-accent fill-accent drop-shadow-[0_0_8px_rgba(250,204,21,0.6)]"
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
