import React from 'react';
import { VOTE_LEVELS } from '../../lib/constants.js';

// Reparto de las notas de la comunidad en la ficha: una fila por nivel de voto, de mayor a menor como en la maqueta.
// Las barras son decorativas (`aria-hidden`) y el recuento va en texto para que lo lean los lectores de pantalla;
// los niveles salen de `VOTE_LEVELS`, así que siguen a los botones de votar. Sin votos no se pintan barras.
const VoteDistribution = ({ distribution }) => {
  if (!Array.isArray(distribution)) return null;

  const rows = [...VOTE_LEVELS].reverse().map((level) => ({
    level,
    count: distribution.find((d) => d.score === level.score)?.count ?? 0,
  }));
  const max = Math.max(...rows.map((row) => row.count));
  if (max === 0) return null;

  return (
    <ul aria-label="Distribución de votos" data-vote-distribution className="mt-2 grid gap-1.5 text-caption">
      {rows.map(({ level, count }) => (
        <li key={level.score} data-score={level.score} className="grid grid-cols-dist items-center gap-2">
          <span className="text-ink">{level.label}</span>
          <span aria-hidden="true" className="h-2 overflow-hidden rounded-full bg-line">
            <i className="block h-full origin-left animate-grow bg-ink-muted" style={{ width: `${(count / max) * 100}%` }} />
          </span>
          <span className="text-right tabular-nums text-ink-muted">
            <span data-count>{count}</span>
            <span className="sr-only"> {count === 1 ? 'voto' : 'votos'}</span>
          </span>
        </li>
      ))}
    </ul>
  );
};

export default VoteDistribution;
