import React from 'react';
import { Loader2, SearchX } from 'lucide-react';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import FadeUp from '../components/animations/FadeUp.jsx';
import OnboardingInterestsStep from '../components/onboarding/OnboardingInterestsStep.jsx';
import OnboardingRatingStep from '../components/onboarding/OnboardingRatingStep.jsx';
// Hooks
import { useOnboardingPage } from '../hooks/pages/useOnboardingPage.js';
import { ONBOARDING_STEPS } from '../lib/onboarding.js';

// Onboarding (F2-07): intereses y speed-rating. Aparece al entrar si la cuenta no tiene intereses; se puede saltar.
const OnboardingPage = () => {
  const { state, actions } = useOnboardingPage();
  const isInterests = state.step === ONBOARDING_STEPS.INTERESTS;

  const renderRating = () => {
    if (state.isLoadingItems) {
      return <FeedbackState icon={Loader2} isLoading description="Preparando las propuestas..." />;
    }
    if (state.isItemsError || state.isEmpty) {
      return (
        <FeedbackState
          icon={SearchX}
          title="No hay nada que valorar ahora"
          description="Puedes empezar a votar desde la portada."
          actionText="Ir a la portada"
          onAction={actions.finish}
        />
      );
    }
    if (state.item) {
      return (
        <OnboardingRatingStep
          item={state.item}
          index={state.index}
          total={state.total}
          onRate={actions.rate}
          onSkipItem={actions.skipItem}
          onSkip={actions.skipOnboarding}
        />
      );
    }
    return null;
  };

  return (
    <div className="flex w-full flex-col">
      <div className="mb-8 shrink-0">
        <SectionHeader
          size="large"
          title={isInterests ? '¿Qué te' : 'Valora'}
          highlight={isInterests ? 'interesa?' : 'lo que conozcas'}
          subtitle={isInterests
            ? 'Elige una o varias temáticas para empezar.'
            : 'Puntúa lo que hayas visto o jugado; si no lo conoces, pasa al siguiente.'}
        />
      </div>

      <FadeUp className="mx-auto flex w-full max-w-3xl flex-col">
        {isInterests ? (
          <OnboardingInterestsStep
            categories={state.categories}
            selectedSlugs={state.selectedSlugs}
            onToggle={actions.toggleCategory}
            onContinue={actions.submitInterests}
            onSkip={actions.skipOnboarding}
            isLoading={state.isLoadingCategories}
            isSaving={state.isSavingInterests}
          />
        ) : renderRating()}
      </FadeUp>
    </div>
  );
};

export default OnboardingPage;
