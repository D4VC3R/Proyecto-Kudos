import React from 'react';
import InputField from '../../ui/InputField.jsx';
import TextAreaField from '../../ui/TextAreaField.jsx';

const AdminListFormBody = ({ formData, setFormData }) => {
  return (
    <>
      <InputField
        label="Título"
        placeholder="Ej. Para ver en una tarde de lluvia"
        name="title"
        maxLength={120}
        value={formData.title}
        onChange={(e) => setFormData({ ...formData, title: e.target.value })}
      />
      <div className="mt-4">
        <TextAreaField
          label="Descripción"
          placeholder="Cuenta de qué va la lista"
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        />
      </div>
      <label className="flex items-center gap-2 text-sm font-bold text-slate-700 cursor-pointer mt-4">
        <input
          type="checkbox"
          checked={formData.is_ranked}
          onChange={(e) => setFormData({ ...formData, is_ranked: e.target.checked })}
          className="rounded text-primary focus:ring-blue-500 w-4 h-4 cursor-pointer"
        />
        Lista numerada (muestra el puesto)
      </label>
    </>
  );
};

export default AdminListFormBody;
