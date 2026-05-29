import React from 'react';
import InputField from '../../ui/InputField.jsx';

const AdminUserBanBody = ({userName, banParams, setBanParams}) => {
  return (
    <div className="flex flex-col gap-4">
      <p className="text-nav-item">
        ¿Bloquear el acceso a <span className="font-bold">{userName}?</span>.
      </p>

      <InputField
        label="Motivo (Opcional)"
        placeholder="Ej. Incumplimiento de normas"
        value={banParams.reason}
        onChange={(e) => setBanParams({ ...banParams, reason: e.target.value })}
      />

      <label className="flex items-center gap-2 text-sm font-bold text-slate-700 cursor-pointer mt-2">
        <input
          type="checkbox"
          checked={banParams.is_permanent}
          onChange={(e) => setBanParams({ ...banParams, is_permanent: e.target.checked })}
          className="rounded text-primary focus:ring-blue-500 w-4 h-4 cursor-pointer"
        />
        Baneo permanente
      </label>

      {!banParams.is_permanent && (
        <InputField
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