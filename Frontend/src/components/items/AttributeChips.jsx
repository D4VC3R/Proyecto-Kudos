import React from 'react';
import Chip from '../ui/Chip.jsx';
import { getAttributeEntries } from '../../lib/attributes.js';
import { resolveCategoryIcon } from '../../lib/formatters.js';

// Atributos del catálogo a partir de attribute_schema.
// variant="detail": todos los atributos con valor, con su etiqueta. variant="card": el icono de la categoría, el primer
// valor de los atributos de show_in_card y un chip con el segundo (año y género en películas, tipo y país en destinos).
const AttributeChips = ({ schema, attributes, variant = 'detail', category, className = '' }) => {
  const isCard = variant === 'card';
  const entries = getAttributeEntries(schema, attributes, { cardOnly: isCard });

  if (isCard) {
    const [first, second] = entries.flatMap((entry) => entry.values.map((value) => ({ label: entry.label, value })));
    if (!category && !first) return null;
    const Icon = category ? resolveCategoryIcon(category) : null;

    return (
      <div className={`flex flex-wrap items-center gap-1.5 text-caption text-ink-muted ${className}`}>
        {category && (
          <>
            <span title={category.name}>
              <Icon size={14} aria-hidden="true" className="text-cat-icon" />
            </span>
            <span className="sr-only">{category.name}{first ? ', ' : ''}</span>
          </>
        )}
        {first && <span title={first.label} className="tabular-nums">{first.value}</span>}
        {second && (
          <Chip title={second.label} className="max-w-full">
            <span className="truncate">{second.value}</span>
          </Chip>
        )}
      </div>
    );
  }

  if (entries.length === 0) return null;

  return (
    <dl className={`grid grid-cols-1 gap-4 sm:grid-cols-2 ${className}`}>
      {entries.map((entry) => (
        <div key={entry.key} className="flex flex-col gap-1.5">
          <dt className="text-xs font-bold uppercase tracking-wide text-slate-500">{entry.label}</dt>
          <dd className="flex flex-wrap gap-2">
            {entry.values.map((value, index) => (
              <Chip key={`${entry.key}-${index}`}>{value}</Chip>
            ))}
          </dd>
        </div>
      ))}
    </dl>
  );
};

export default AttributeChips;
