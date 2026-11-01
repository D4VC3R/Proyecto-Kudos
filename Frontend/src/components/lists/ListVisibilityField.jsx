import React from 'react';
import { LIST_VISIBILITY_OPTIONS } from '../../lib/lists.js';

// Selector de visibilidad de una lista (F3-01): privada, no listada o pública, con su explicación.
const ListVisibilityField = ({ value, onChange, disabled = false, name = 'list-visibility' }) => (
  <fieldset className="flex flex-col gap-2">
    <legend className="mb-2 text-sm font-bold text-slate-700">Visibilidad</legend>
    {LIST_VISIBILITY_OPTIONS.map((option) => (
      <label key={option.value} className="flex cursor-pointer items-start gap-2 text-sm text-text-highlight">
        <input
          type="radio"
          name={name}
          value={option.value}
          checked={value === option.value}
          onChange={() => onChange(option.value)}
          disabled={disabled}
          className="mt-0.5 h-4 w-4 shrink-0 accent-blue-600"
        />
        <span className="flex flex-col">
          <span className="font-bold">{option.label}</span>
          <span className="text-text-normal">{option.description}</span>
        </span>
      </label>
    ))}
  </fieldset>
);

export default ListVisibilityField;
