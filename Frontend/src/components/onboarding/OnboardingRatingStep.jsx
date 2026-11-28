import React from 'react';
import { AnimatePresence } from 'framer-motion';
import ItemDetailPoster from '../items/ItemDetailPoster.jsx';
import AnimatedItem from '../animations/AnimatedItem.jsx';
import VoteScoreLevels from '../votes/VoteScoreLevels.jsx';
import Button from '../ui/Button.jsx';

// Paso 2 del onboarding: un ítem cada vez, con los niveles de la votación. Sin teclas ni paso anterior.
const OnboardingRatingStep = ({ item, index, total, onRate, onSkipItem, onSkip }) => {
  return (
    <div data-onboarding-rating className="rounded-md border border-line bg-surface p-4 md:p-6">
      <p aria-live="polite" className="mb-4 text-caption font-semibold uppercase tracking-widest text-kicker">
        {index + 1} de {total}
      </p>

      <div className="grid w-full gap-6 md:grid-cols-vote md:items-start md:gap-8">
        <div>
          <AnimatePresence mode="wait">
            <AnimatedItem key={item.id} itemKey={item.id}>
              <div className="w-full">
                <ItemDetailPoster item={item} showCredit={false} />
                {item.category?.name && (
                  <p className="mt-3 text-caption font-semibold uppercase tracking-widest text-kicker">{item.category.name}</p>
                )}
                <h2 className="mt-1 font-display text-h2 text-ink">{item.name}</h2>
              </div>
            </AnimatedItem>
          </AnimatePresence>
        </div>

        {/* Fuera del ítem animado: si se montara de nuevo con cada ítem, un clic durante la salida valoraría el siguiente sin verlo. */}
        <div className="min-w-0">
          <VoteScoreLevels onRate={onRate} showKeys={false} stacked autoFocus />

          <div className="mt-4 flex flex-col gap-2 sm:flex-row">
            <Button onClick={onSkipItem} variant="secondary">
              No lo conozco
            </Button>
            <Button onClick={onSkip} variant="ghost">
              Saltar
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OnboardingRatingStep;
