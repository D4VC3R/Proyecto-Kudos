import { useState } from 'react';

export const AdminCommentHideForm = ({ isMutating, onCancel, onConfirm }) => {
  const [reason, setReason] = useState('');

  return (
    <form
      className="mt-3 space-y-2 rounded-lg border border-slate-700 bg-slate-950 p-3"
      onSubmit={(event) => {
        event.preventDefault();
        onConfirm({ reason: reason.trim() });
      }}
    >
      <label className="block space-y-1 text-xs">
        <span>Motivo de ocultacion (opcional)</span>
        <textarea
          className="min-h-16 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-slate-100"
          onChange={(event) => setReason(event.target.value)}
          value={reason}
        />
      </label>

      <div className="flex gap-2">
        <button
          className="rounded-md bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60"
          disabled={isMutating}
          type="submit"
        >
          Confirmar ocultacion
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

