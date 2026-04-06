import { useState } from 'react';

export const AdminUserBanForm = ({ onCancel, onConfirm, isMutating }) => {
  const [reason, setReason] = useState('');
  const [isPermanent, setIsPermanent] = useState(false);
  const [days, setDays] = useState('7');

  return (
    <form
      className="mt-3 space-y-2 rounded-lg border border-slate-700 bg-slate-950 p-3"
      onSubmit={(event) => {
        event.preventDefault();
        onConfirm({ reason, isPermanent, days: Number(days) || 1 });
      }}
    >
      <label className="block space-y-1 text-xs">
        <span>Motivo del baneo</span>
        <textarea
          className="min-h-16 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-slate-100"
          onChange={(event) => setReason(event.target.value)}
          required
          value={reason}
        />
      </label>

      <label className="flex items-center gap-2 text-xs text-slate-200">
        <input checked={isPermanent} onChange={(event) => setIsPermanent(event.target.checked)} type="checkbox" />
        Baneo permanente
      </label>

      {!isPermanent && (
        <label className="block space-y-1 text-xs">
          <span>Dias</span>
          <input
            className="w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-slate-100"
            min={1}
            onChange={(event) => setDays(event.target.value)}
            required
            type="number"
            value={days}
          />
        </label>
      )}

      <div className="flex gap-2">
        <button
          className="rounded-md bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50"
          disabled={isMutating}
          type="submit"
        >
          Confirmar baneo
        </button>
        <button
          className="rounded-md border border-slate-700 px-3 py-1 text-xs font-medium text-slate-200 hover:bg-slate-800"
          onClick={onCancel}
          type="button"
        >
          Cancelar
        </button>
      </div>
    </form>
  );
};

