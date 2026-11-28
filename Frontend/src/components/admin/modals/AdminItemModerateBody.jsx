import React from 'react';
import Select from '../../ui/Select.jsx';
import Textarea from '../../ui/Textarea.jsx';

const AdminItemModerateBody = ({itemName, modStatus, setModStatus, modReason, setModReason}) => {
  return (
    <>
      <p className="text-small text-ink-muted">
        Estás moderando <span className="font-bold text-ink">{itemName}</span>. Selecciona un nuevo estado.
      </p>
      <Select
        label="Nuevo estado"
        value={modStatus}
        onChange={(e) => setModStatus(e.target.value)}
        options={[
          { value: 'active', label: 'Activo' },
          { value: 'inactive', label: 'Inactivo' },
        ]}
      />
      <div className="mt-2">
        <Textarea
          label={`Razón ${modStatus === 'inactive' ? '(Obligatoria al desactivar)' : ''}`}
          placeholder="Motivo de la moderación para el historial..."
          value={modReason}
          onChange={(e) => setModReason(e.target.value)}
          required={modStatus === 'inactive'}
        />
      </div>
    </>
  );
};

export default AdminItemModerateBody;