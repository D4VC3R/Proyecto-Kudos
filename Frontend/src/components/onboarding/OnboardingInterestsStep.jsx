import React from 'react';
import { Check, Loader2 } from 'lucide-react';
import Button from '../ui/Button.jsx';
import FeedbackState from '../ui/FeedbackState.jsx';

// Paso 1 del onboarding: rejilla de categorías para elegir los intereses.
const OnboardingInterestsStep = ({ categories, selectedSlugs, onToggle, onContinue, onSkip, isLoading, isSaving }) => {
  if (isLoading) {
    return <FeedbackState icon={Loader2} isLoading description="Cargando las temáticas..." />;
  }

  return (
    <div className="flex flex-col gap-6">
      <div role="group" aria-label="Temáticas" className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        {categories.map((category) => {
          const isSelected = selectedSlugs.includes(category.slug);
          return (
            <Button
              key={category.slug}
              onClick={() => onToggle(category.slug)}
              variant="secondary"
              size="lg"
              aria-pressed={isSelected}
              icon={isSelected ? Check : undefined}
              className="justify-start"
              disabled={isSaving}
            >
              {category.name}
            </Button>
          );
        })}
      </div>

      <div className="flex flex-col gap-2">
        <Button
          onClick={onContinue}
          variant="primary"
          size="lg"
          isFullWidth
          disabled={selectedSlugs.length === 0}
          isLoading={isSaving}
        >
          Continuar
        </Button>
        <Button onClick={onSkip} variant="ghost" isFullWidth disabled={isSaving}>
          Saltar
        </Button>
      </div>
    </div>
  );
};

export default OnboardingInterestsStep;
