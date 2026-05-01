import React from 'react';
import { InputField } from '../common/InputField';

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
      <div className="flex flex-col gap-2 mt-4">
        <label className="text-sm font-bold text-slate-700">Descripción (Opcional)</label>
        <textarea
          className="w-full rounded-2xl border-slate-200 bg-slate-50 p-4 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 transition-all font-medium resize-none min-h-[100px]"
          placeholder="Descripción de la categoría..."
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        />
      </div>
    </>
  );
};