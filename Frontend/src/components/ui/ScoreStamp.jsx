import React from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';

// Sello con la nota de un ítem. `score` ya viene formateado con `formatScore` (o `null` si no hay votos).
// `sm` es el sello de una tarjeta y `lg` el de la ficha, que se estampa al aparecer.
const ScoreStamp = ({ score, size = 'sm', className }) => {
  if (size === 'lg') {
    if (!score) {
      return (
        <div className={twMerge(clsx(
          'grid h-28 w-28 shrink-0 -rotate-3 place-content-center rounded-full border-2 border-line-strong text-center',
          className,
        ))}>
          <span className="text-small text-ink-muted">Sin nota</span>
        </div>
      );
    }

    return (
      <div
        role="img"
        aria-label={`Nota media ${score} de 10`}
        className={twMerge(clsx(
          'relative grid h-28 w-28 shrink-0 -rotate-3 place-content-center rounded-full border-2 border-stamp text-center text-stamp animate-stamp',
          className,
        ))}
      >
        <span aria-hidden="true" className="absolute inset-1 rounded-full border border-stamp" />
        <b className="font-display text-4xl font-semibold leading-none tabular-nums">{score}</b>
        <span className="text-caption text-ink-muted">de 10</span>
      </div>
    );
  }

  if (!score) {
    return (
      <span className={twMerge(clsx(
        'inline-block rounded border border-line-strong px-1.5 py-1 text-caption font-medium leading-none text-ink-muted',
        className,
      ))}>
        Sin nota
      </span>
    );
  }

  return (
    <span className={twMerge(clsx(
      'inline-block rounded border border-stamp px-1.5 py-1 font-display text-small font-semibold leading-none tabular-nums text-stamp',
      className,
    ))}>
      <span aria-hidden="true">{score}</span>
      <span className="sr-only">Nota {score} de 10</span>
    </span>
  );
};

export default ScoreStamp;
