import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useNextCategoryItem } from '../categories/useCategoryQueries.js';
import { useCreateVote } from '../votes/useVoteMutations.js';
import { VOTE_STEPS, VOTE_LEVELS } from '../../lib/constants.js';

// Las teclas de atajo no se aplican mientras se escribe (p. ej., en los comentarios).
const isTypingTarget = (target) =>
  target instanceof HTMLElement &&
  (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

/**
 * Hook personalizado para manejar la lógica de la página de votación.
 * Encapsula la obtención del siguiente ítem a votar, la votación en dos pasos ("¿Lo conoces?" y después valorar
 * o indicar interés), los atajos de teclado y la lógica de mostrar/ocultar comentarios.
 *
 * Teclado: S/N en el paso 1; 1-5 (de peor a mejor) al valorar; 1/2 en el paso de interés; Esc vuelve al paso 1.
 *
 * @returns {object} Objeto con el estado y funciones necesarias para la página de votación.
 * - state: Contiene el slug de la categoría, el ítem actual a votar, el paso de la votación, si se están mostrando los comentarios, y estados de carga.
 * - actions: Funciones para avanzar/volver entre pasos, votar (nota, wishlist o "no me interesa") y el toggle de comentarios.
 * */
export const useVotePage = () => {
  const { categorySlug } = useParams();
  const [showComments, setShowComments] = useState(false);
  const [step, setStep] = useState(VOTE_STEPS.KNOW);

  // Peticiones de red
  const { data, isLoading, isFetching } = useNextCategoryItem(categorySlug);
  const { mutate: submitVote, isPending } = useCreateVote();

  const item = data?.data;
  const isBackgroundUpdating = isFetching && !isLoading;
  // Bloquea botones y teclado mientras se envía el voto o llega el siguiente ítem.
  const isBusy = isPending || isFetching;


  useEffect(() => {
    setShowComments(false);
    setStep(VOTE_STEPS.KNOW);
  }, [item?.id]);

  const handleKnown = () => {
    setStep(VOTE_STEPS.RATE);
  };

  const handleUnknown = () => {
    setStep(VOTE_STEPS.INTEREST);
  };

  const handleBack = () => {
    setStep(VOTE_STEPS.KNOW);
  };

  const handleVote = (score) => {
    submitVote({ item_id: item.id, type: 'vote', score, categorySlug });
  };

  const handleWant = () => {
    submitVote({ item_id: item.id, type: 'want', categorySlug });
  };

  const handleNotInterested = () => {
    submitVote({ item_id: item.id, type: 'not_interested', categorySlug });
  };

  const toggleComments = () => {
    setShowComments((prev) => !prev);
  };

  useEffect(() => {
    if (!item) return undefined;

    const handleKeyDown = (event) => {
      if (event.defaultPrevented || event.repeat || event.ctrlKey || event.metaKey || event.altKey) return;
      if (isBusy || isTypingTarget(event.target)) return;

      const key = event.key.toLowerCase();

      if (step === VOTE_STEPS.KNOW) {
        if (key === 's') {
          event.preventDefault();
          handleKnown();
        } else if (key === 'n') {
          event.preventDefault();
          handleUnknown();
        }
        return;
      }

      if (key === 'escape') {
        event.preventDefault();
        handleBack();
        return;
      }

      if (step === VOTE_STEPS.RATE) {
        const level = VOTE_LEVELS.find((option) => option.key === key);
        if (level) {
          event.preventDefault();
          handleVote(level.score);
        }
        return;
      }

      if (key === '1') {
        event.preventDefault();
        handleWant();
      } else if (key === '2') {
        event.preventDefault();
        handleNotInterested();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [item, step, isBusy, categorySlug, submitVote]);

  return {
    state: {
      categorySlug,
      item,
      step,
      showComments,
      isLoading,
      isPending,
      isBusy,
      isBackgroundUpdating,
    },
    actions: {
      handleKnown,
      handleUnknown,
      handleBack,
      handleVote,
      handleWant,
      handleNotInterested,
      toggleComments,
    }
  };
};