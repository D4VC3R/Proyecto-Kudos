import React from 'react';
import Radio from '../ui/Radio.jsx';
import { FIELD_LABEL } from '../ui/fieldStyles.js';
import { LIST_VISIBILITY_OPTIONS } from '../../lib/lists.js';

// Selector de visibilidad de una lista (F3-01): privada, no listada o pública, con su explicación.
const ListVisibilityField = ({ value, onChange, disabled = false, name = 'list-visibility' }) => (
  <fieldset className="flex flex-col gap-2">
    <legend className={`mb-2 ${FIELD_LABEL}`}>Visibilidad</legend>
    {LIST_VISIBILITY_OPTIONS.map((option) => (
      <Radio
        key={option.value}
        label={option.label}
        description={option.description}
        name={name}
        value={option.value}
        checked={value === option.value}
        onChange={() => onChange(option.value)}
        disabled={disabled}
      />
    ))}
  </fieldset>
);

export default ListVisibilityField;
