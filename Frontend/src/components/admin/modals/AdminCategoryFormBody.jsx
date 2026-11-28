import React from 'react';
import Input from '../../ui/Input.jsx';
import Textarea from '../../ui/Textarea.jsx';

const AdminCategoryFormBody = ({ formData, setFormData }) => {
  return (
    <>
      <Input
        label="Nombre de la categoría"
        placeholder="Ej. Diseño..."
        name="name"
        value={formData.name}
        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
      />
      <div className="mt-4">
        <Textarea
          label="Descripción"
          placeholder="No me seas rancio..."
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        />
      </div>
    </>
  );
};

export default AdminCategoryFormBody;