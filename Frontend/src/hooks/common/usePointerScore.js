import { useState } from 'react';

const getScoreFromEvent = (clientX, currentTarget) => {
  const rect = currentTarget.getBoundingClientRect();
  const x = clientX - rect.left;
  const width = rect.width;

  let calculatedScore = Math.ceil((x / width) * 20) / 2;
  return Math.min(Math.max(calculatedScore, 0.5), 10);
};

export const usePointerScore = (onVote, isPending) => {
  const [hoverScore, setHoverScore] = useState(0);

  const events = {
    onPointerMove: (e) => {
      if (!isPending) setHoverScore(getScoreFromEvent(e.clientX, e.currentTarget));
    },
    onPointerLeave: () => {
      if (!isPending) setHoverScore(0);
    },
    onClick: (e) => {
      if (!isPending) onVote(getScoreFromEvent(e.clientX, e.currentTarget));
    }
  };

  return { hoverScore, events };
};