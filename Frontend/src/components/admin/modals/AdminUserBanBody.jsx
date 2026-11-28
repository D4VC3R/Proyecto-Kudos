import React from 'react';
import Checkbox from '../../ui/Checkbox.jsx';
import Input from '../../ui/Input.jsx';

const AdminUserBanBody = ({userName, banParams, setBanParams}) => {
  return (
    <div className="flex flex-col gap-4">
      <p className="text-ink-muted">
        ¿Bloquear el acceso a <span className="font-bold">{userName}?</span>.
      </p>

      <Input
        label="Motivo"
        placeholder="¿Qué ha hecho el nota?"
        value={banParams.reason}
        onChange={(e) => setBanParams({ ...banParams, reason: e.target.value })}
      />

      <Checkbox
        className="mt-2"
        label="Baneo permanente"
        checked={banParams.is_permanent}
        onChange={(e) => setBanParams({ ...banParams, is_permanent: e.target.checked })}
      />

      {!banParams.is_permanent && (
        <Input
          label="Días de suspensión"
          type="number"
          min="1"
          placeholder="No te cortes..."
          value={banParams.days || ''}
          onChange={(e) => setBanParams({ ...banParams, days: e.target.value })}
        />
      )}
    </div>
  );
};

export default AdminUserBanBody;