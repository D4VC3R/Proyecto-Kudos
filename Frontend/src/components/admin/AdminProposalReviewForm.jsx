import { useState } from 'react';

export const AdminProposalReviewForm = ({ isMutating, onCancel, onConfirm }) => {
  const [status, setStatus] = useState('accepted');
  const [adminNotes, setAdminNotes] = useState('');

  const requiresNotes = status === 'rejected' || status === 'changes_requested';

  return (
    <form
      className="mt-3 space-y-2 rounded-lg border border-slate-700 bg-slate-950 p-3"
      onSubmit={(event) => {
        event.preventDefault();
        onConfirm({ status, adminNotes: adminNotes.trim() });
      }}
    >
      <label className="block space-y-1 text-xs">
        <span>Estado de revision</span>
        <select
          className="w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-slate-100"
          onChange={(event) => setStatus(event.target.value)}
          value={status}
        >
          <option value="accepted">accepted</option>
          <option value="rejected">rejected</option>
          <option value="changes_requested">changes_requested</option>
        </select>
      </label>

      <label className="block space-y-1 text-xs">
        <span>Notas admin {requiresNotes ? '(obligatorias)' : '(opcionales)'}</span>
        <textarea
          className="min-h-16 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-slate-100"
          onChange={(event) => setAdminNotes(event.target.value)}
          required={requiresNotes}
          value={adminNotes}
        />
      </label>

      <div className="flex gap-2">
        <button
          className="rounded-md bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
          disabled={isMutating}
          type="submit"
        >
          Confirmar revision
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

