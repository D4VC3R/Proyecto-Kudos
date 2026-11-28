import React from 'react';
import Input from '../../ui/Input.jsx';
import Checkbox from '../../ui/Checkbox.jsx';
import Textarea from '../../ui/Textarea.jsx';

const AdminListFormBody = ({ formData, setFormData }) => {
  return (
    <>
      <Input
        label="Título"
        placeholder="Ej. Para ver en una tarde de lluvia"
        name="title"
        maxLength={120}
        value={formData.title}
        onChange={(e) => setFormData({ ...formData, title: e.target.value })}
      />
      <div className="mt-4">
        <Textarea
          label="Descripción"
          placeholder="Cuenta de qué va la lista"
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        />
      </div>
      <Checkbox
        className="mt-4"
        label="Lista numerada (muestra el puesto)"
        checked={formData.is_ranked}
        onChange={(e) => setFormData({ ...formData, is_ranked: e.target.checked })}
      />
    </>
  );
};

export default AdminListFormBody;
