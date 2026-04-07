import { useState } from 'react';
import { useAdminUserDetailContext } from '../../hooks/admin/useAdminContexts';
import { AdminUserBanForm } from './AdminUserBanForm';

export const AdminUserDetailActions = () => {
  const { user, isSelfUser, isMutating, banUser, unbanUser, revokeSessions } = useAdminUserDetailContext();
  const [isBanFormVisible, setIsBanFormVisible] = useState(false);

  const content = isSelfUser ? (
    <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
      <p className="text-sm text-slate-400">Por seguridad no se permiten acciones administrativas sobre tu propia cuenta.</p>
    </div>
  ) : (
    <section className="space-y-3 rounded-2xl border border-slate-800 bg-slate-900 p-5">
      <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Acciones</h3>

      <div className="flex flex-wrap gap-2">
        {!user?.is_banned && (
          <button
            className="rounded-md bg-red-700 px-3 py-2 text-sm font-medium text-white hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={isMutating}
            onClick={() => setIsBanFormVisible(true)}
            type="button"
          >
            Banear usuario
          </button>
        )}

        {user?.is_banned && (
          <button
            className="rounded-md bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={isMutating}
            onClick={unbanUser}
            type="button"
          >
            Desbanear usuario
          </button>
        )}

        <button
          className="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-100 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
          disabled={isMutating}
          onClick={revokeSessions}
          type="button"
        >
          Revocar sesiones
        </button>
      </div>

      {isBanFormVisible && (
        <AdminUserBanForm
          isMutating={isMutating}
          onCancel={() => setIsBanFormVisible(false)}
          onConfirm={async ({ reason, isPermanent, days }) => {
            await banUser({ reason, isPermanent, days });
            setIsBanFormVisible(false);
          }}
        />
      )}
    </section>
  );

  return content;
};
