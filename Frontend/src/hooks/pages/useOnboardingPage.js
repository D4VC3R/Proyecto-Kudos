import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useCategories } from '../categories/useCategoryQueries.js';
import { useOnboardingItems } from '../onboarding/useOnboardingQueries.js';
import { useUpdateInterests } from '../users/useUserMutations.js';
import { useCreateVote } from '../votes/useVoteMutations.js';
import { useSessionStore } from '../../store/useSessionStore.js';
import { getReturnPath } from '../../lib/authRedirect.js';
import { ONBOARDING_STEPS } from '../../lib/onboarding.js';

const NO_ITEMS = [];

/**
 * Lógica de la página de onboarding (F2-07): paso 1, elegir intereses (categorías); paso 2, speed-rating de
 * los ítems populares de esas categorías con los mismos niveles que la votación.
 *
 * - Terminar lleva a la portada; saltar, a la página de origen del login (F0-24). En ambos casos el aviso
 *   se apaga hasta el siguiente login (onboardingPending, en el store de sesión).
 * - "No lo conozco" no envía nada: solo pasa al siguiente.
 *
 * @returns {{state: object, actions: object}}
 */
export const useOnboardingPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const setOnboardingPending = useSessionStore((s) => s.setOnboardingPending);

  const [step, setStep] = useState(ONBOARDING_STEPS.INTERESTS);
  const [selected, setSelected] = useState(() => {
    const interests = useSessionStore.getState().user?.interests;
    return Array.isArray(interests) ? interests : [];
  });
  const [index, setIndex] = useState(0);

  const { data: categories = NO_ITEMS, isLoading: isLoadingCategories } = useCategories();
  // Los slugs en el orden de las categorías; así se descartan los que ya no existen o están inactivos.
  const selectedSlugs = useMemo(
    () => categories.filter((category) => selected.includes(category.slug)).map((category) => category.slug),
    [categories, selected],
  );

  const { mutate: saveInterests, isPending: isSavingInterests } = useUpdateInterests();
  const { mutate: submitVote } = useCreateVote({ showSuccessToast: false });
  const {
    data: items = NO_ITEMS,
    isLoading: isLoadingItems,
    isError: isItemsError,
    isSuccess: isItemsSuccess,
  } = useOnboardingItems(selectedSlugs, step === ONBOARDING_STEPS.RATING);

  const leave = (path) => {
    setOnboardingPending(false);
    navigate(path, { replace: true });
  };

  const toggleCategory = (slug) => {
    setSelected((prev) => (prev.includes(slug) ? prev.filter((s) => s !== slug) : [...prev, slug]));
  };

  const submitInterests = () => {
    if (selectedSlugs.length === 0) return;
    saveInterests(selectedSlugs, {
      onSuccess: () => {
        setIndex(0);
        setStep(ONBOARDING_STEPS.RATING);
      },
    });
  };

  const item = items[index] ?? null;

  // Valora y pasa al siguiente sin esperar la respuesta.
  const rate = (score) => {
    if (!item) return;
    submitVote({ item_id: item.id, type: 'vote', score });
    setIndex((prev) => prev + 1);
  };

  // "No lo conozco": no envía nada.
  const skipItem = () => {
    setIndex((prev) => prev + 1);
  };

  const skipOnboarding = () => leave(getReturnPath(location.state?.from));
  const finish = () => leave('/');

  // Al valorar el último ítem, a la portada.
  useEffect(() => {
    if (step === ONBOARDING_STEPS.RATING && isItemsSuccess && items.length > 0 && index >= items.length) {
      setOnboardingPending(false);
      navigate('/', { replace: true });
    }
  }, [step, isItemsSuccess, items.length, index, navigate, setOnboardingPending]);

  return {
    state: {
      step,
      categories,
      selectedSlugs,
      isLoadingCategories,
      isSavingInterests,
      item,
      index,
      total: items.length,
      isLoadingItems,
      isItemsError,
      isEmpty: isItemsSuccess && items.length === 0,
    },
    actions: {
      toggleCategory,
      submitInterests,
      rate,
      skipItem,
      skipOnboarding,
      finish,
    },
  };
};
