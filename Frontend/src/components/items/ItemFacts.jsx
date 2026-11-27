import React from 'react';

// Datos de la ficha (duración, país, fecha…) en una cuadrícula de etiqueta y valor.
const ItemFacts = ({ entries = [] }) => {
  if (entries.length === 0) return null;

  return (
    <dl className="mt-5 grid grid-cols-2 gap-3 border-y border-line py-3 sm:grid-cols-3">
      {entries.map((entry) => (
        <div key={entry.key}>
          <dt className="text-caption font-semibold uppercase tracking-widest text-ink-muted">{entry.label}</dt>
          <dd className="mt-1 text-small text-ink">{entry.values.join(', ')}</dd>
        </div>
      ))}
    </dl>
  );
};

export default ItemFacts;
