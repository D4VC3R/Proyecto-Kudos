import React from 'react';
import InputField from '../ui/InputField.jsx';
import TextAreaField from '../ui/TextAreaField.jsx';
import ListVisibilityField from './ListVisibilityField.jsx';
import { LIST_DESCRIPTION_MAX_LENGTH, LIST_TITLE_MAX_LENGTH } from '../../lib/lists.js';

// Campos de datos de una lista propia (F3-01): título, descripción, visibilidad y numeración.
// Se usan en el modal de creación y en la página de edición. `visibilityDisabled` bloquea la visibilidad (moderación).
const MyListFormFields = ({ formData, setFormData, disabled = false, visibilityDisabled = false, idPrefix = 'my-list' }) => (
  <div className="flex flex-col gap-4">
    <InputField
      label="Título"
      placeholder="Ej. Para ver en una tarde de lluvia"
      name={`${idPrefix}-title`}
      maxLength={LIST_TITLE_MAX_LENGTH}
      value={formData.title}
      onChange={(e) => setFormData({ ...formData, title: e.target.value })}
      disabled={disabled}
    />
    <TextAreaField
      label="Descripción"
      placeholder="Cuenta de qué va la lista"
      rows={3}
      maxLength={LIST_DESCRIPTION_MAX_LENGTH}
      value={formData.description}
      onChange={(e) => setFormData({ ...formData, description: e.target.value })}
      disabled={disabled}
    />
    <ListVisibilityField
      name={`${idPrefix}-visibility`}
      value={formData.visibility}
      onChange={(visibility) => setFormData({ ...formData, visibility })}
      disabled={disabled || visibilityDisabled}
    />
    <label className="flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-700">
      <input
        type="checkbox"
        checked={formData.is_ranked}
        onChange={(e) => setFormData({ ...formData, is_ranked: e.target.checked })}
        disabled={disabled}
        className="h-4 w-4 cursor-pointer rounded text-primary focus:ring-blue-500"
      />
      Lista numerada (muestra el puesto)
    </label>
  </div>
);

export default MyListFormFields;
