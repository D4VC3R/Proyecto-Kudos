import React from 'react';
import { AnimatePresence } from 'framer-motion';
import ItemImage from '../items/ItemImage.jsx';
import AnimatedItem from '../animations/AnimatedItem.jsx';
import VoteScoreLevels from '../votes/VoteScoreLevels.jsx';
import Button from '../ui/Button.jsx';

// Paso 2 del onboarding (F2-07): speed-rating. Un ítem cada vez, con los niveles de la votación (F2-04).
// Sin onBack ni teclas: aquí no hay paso anterior ni atajos.
const OnboardingRatingStep = ({ item, index, total, onRate, onSkipItem, onSkip }) => {
  return (
    <div className="w-full bg-surface rounded-3xl p-4 md:p-6 shadow-xl ring-1 ring-slate-200 flex flex-col relative overflow-hidden">
      <p className="text-center text-sm font-bold text-slate-400 uppercase tracking-widest mb-4" aria-live="polite">
        {index + 1} de {total}
      </p>

      <div className="flex justify-center items-center w-full mb-6">
        <AnimatePresence mode="wait">
          <AnimatedItem key={item.id} itemKey={item.id}>
            <ItemImage item={item} variant="thumb" />
          </AnimatedItem>
        </AnimatePresence>
      </div>

      <VoteScoreLevels onRate={onRate} showKeys={false} />

      <div className="flex flex-col items-center gap-1">
        <Button onClick={onSkipItem} variant="ghost" color="neutral">
          No lo conozco
        </Button>
        <Button onClick={onSkip} variant="ghost" color="neutral" size="sm">
          Saltar
        </Button>
      </div>
    </div>
  );
};

export default OnboardingRatingStep;
