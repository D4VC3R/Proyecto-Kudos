import { useState } from 'react';

const getScoreFromEvent = (clientX, currentTarget) => {
  const rect = currentTarget.getBoundingClientRect();
  const x = clientX - rect.left;
  const width = rect.width;

  let calculatedScore = Math.ceil((x / width) * 20) / 2;
  return Math.min(Math.max(calculatedScore, 0.5), 10);
};

/**
 * Hook para manejar la puntuación basada en la posición del puntero (ratón o móvil).
 * @param {function} onVote - Función a llamar con la puntuación final al hacer clic.
 * @param {boolean} isPending - Indica si hay una acción pendiente (para deshabilitar la interacción).
 * @returns {object} Objeto con la puntuación de hover y los eventos para manejar el puntero.
 * - `hoverScore`: Puntuación calculada en tiempo real según la posición del puntero.
 * - `events`: Objeto con los eventos `onPointerMove`, `onPointerLeave` y `onClick` para adjuntar al elemento interactivo.
 */
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