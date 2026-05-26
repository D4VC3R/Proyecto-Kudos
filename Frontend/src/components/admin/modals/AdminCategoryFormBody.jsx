import React from 'react';
import { InputField } from '../../common/InputField.jsx';
import { TextAreaField } from '../../common/TextAreaField.jsx';

export const AdminCategoryFormBody = ({ formData, setFormData }) => {
  return (
    <>
      <InputField
        label="Nombre de la categoría"
        placeholder="Ej. Diseño..."
        name="name"
        value={formData.name}
        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
      />
      <div className="mt-4">
        <TextAreaField
          label="Descripción (Opcional)"
          placeholder="Descripción de la categoría..."
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        />
      </div>
    </>
  );
};