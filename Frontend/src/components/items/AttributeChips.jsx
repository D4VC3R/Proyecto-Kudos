import React from 'react';
import Chip from '../ui/Chip.jsx';
import { getAttributeEntries } from '../../lib/attributes.js';
import { resolveCategoryIcon } from '../../lib/formatters.js';

// Atributos del catálogo a partir de attribute_schema.
// variant="detail": los valores de los atributos de show_in_card (menos el año, que va en el kicker) como chips; el resto
// de atributos los pinta ItemFacts. variant="card": el icono de la categoría, el primer valor de los atributos de
// show_in_card y un chip con el segundo (año y género en películas, tipo y país en destinos).
const AttributeChips = ({ schema, attributes, variant = 'detail', category, className = '' }) => {
  const isCard = variant === 'card';
  const entries = getAttributeEntries(schema, attributes, { cardOnly: true });

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

  const chips = entries.filter((entry) => entry.key !== 'year').flatMap((entry) => entry.values.map((value) => ({ ...entry, value })));
  if (chips.length === 0) return null;

  return (
    <div className={`mt-3 flex flex-wrap gap-1.5 ${className}`}>
      {chips.map((chip, index) => (
        <Chip key={`${chip.key}-${index}`} title={chip.label}>{chip.value}</Chip>
      ))}
    </div>
  );
};

export default AttributeChips;
