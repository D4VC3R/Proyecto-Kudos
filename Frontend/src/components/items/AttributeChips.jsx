import React from 'react';
import { getAttributeEntries } from '../../lib/attributes.js';

// En la tarjeta caben pocos chips: como mucho dos valores por atributo (p. ej. dos géneros).
const CARD_MAX_VALUES = 2;

// Chips de los atributos del catálogo a partir de attribute_schema (F1-09).
// variant="detail": todos los atributos con valor, con su etiqueta. variant="card": solo los de show_in_card, sin etiqueta visible, en una fila.
const AttributeChips = ({ schema, attributes, variant = 'detail', className = '' }) => {
  const isCard = variant === 'card';
  const entries = getAttributeEntries(schema, attributes, { cardOnly: isCard });

  if (entries.length === 0) return null;

  if (isCard) {
    return (
      <ul className={`flex flex-nowrap gap-1 overflow-hidden ${className}`}>
        {entries.flatMap((entry) => entry.values.slice(0, CARD_MAX_VALUES).map((value, index) => (
          <li
            key={`${entry.key}-${index}`}
            title={entry.label}
            className="shrink-0 whitespace-nowrap rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold text-text-btn ring-1 ring-white/30 backdrop-blur-sm"
          >
            <span className="sr-only">{entry.label}: </span>
            {value}
          </li>
        )))}
      </ul>
    );
  }

  return (
    <dl className={`grid grid-cols-1 gap-4 sm:grid-cols-2 ${className}`}>
      {entries.map((entry) => (
        <div key={entry.key} className="flex flex-col gap-1.5">
          <dt className="text-xs font-bold uppercase tracking-wide text-slate-500">{entry.label}</dt>
          <dd className="flex flex-wrap gap-2">
            {entry.values.map((value, index) => (
              <span
                key={`${entry.key}-${index}`}
                className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200"
              >
                {value}
              </span>
            ))}
          </dd>
        </div>
      ))}
    </dl>
  );
};

export default AttributeChips;
