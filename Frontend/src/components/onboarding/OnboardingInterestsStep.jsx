import React from 'react';
import { Check, Loader2 } from 'lucide-react';
import Button from '../ui/Button.jsx';
import FeedbackState from '../ui/FeedbackState.jsx';

// Paso 1 del onboarding (F2-07): rejilla de categorías para elegir los intereses.
const OnboardingInterestsStep = ({ categories, selectedSlugs, onToggle, onContinue, onSkip, isLoading, isSaving }) => {
  if (isLoading) {
    return <FeedbackState icon={Loader2} isLoading description="Cargando las temáticas..." />;
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
        {categories.map((category) => {
          const isSelected = selectedSlugs.includes(category.slug);
          return (
            <Button
              key={category.slug}
              onClick={() => onToggle(category.slug)}
              variant={isSelected ? 'solid' : 'ghost'}
              color="primary"
              size="lg"
              aria-pressed={isSelected}
              icon={isSelected ? Check : undefined}
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
          variant="solid"
          color="primary"
          size="lg"
          isFullWidth
          disabled={selectedSlugs.length === 0}
          isLoading={isSaving}
        >
          Continuar
        </Button>
        <Button onClick={onSkip} variant="ghost" color="neutral" isFullWidth disabled={isSaving}>
          Saltar
        </Button>
      </div>
    </div>
  );
};

export default OnboardingInterestsStep;
