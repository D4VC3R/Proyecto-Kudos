import { Link } from 'react-router-dom';
import { useState } from 'react';
import { useAdminUsersContext } from '../../hooks/admin';
import { selectUser, useSessionStore } from '../../store/useSessionStore';
import { AdminUserBanForm } from './AdminUserBanForm';
import { getApiErrorMessage } from '../../lib/apiErrorMap';
import { AdminSortHeaderButton } from './shared/AdminSortHeaderButton';
import { AdminTableStateRow } from './shared/AdminTableStateRow';

export const AdminUsersTable = () => {
  const { users, isMutating, banUser, unbanUser, sortBy, sortDirection, requestSort, isLoadingUsers, isUsersError, usersError } =
      useAdminUsersContext();
  const currentUser = useSessionStore(selectUser);
  const [activeBanUserId, setActiveBanUserId] = useState(null);

  const showLoadingRow = isLoadingUsers;
  const showErrorRow = isUsersError;
  const showEmptyRow = !isLoadingUsers && !isUsersError && users.length === 0;
  const showDataRows = !isLoadingUsers && !isUsersError && users.length > 0;

  return (
      <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table className="min-w-full table-fixed text-left text-sm">
          <colgroup>
            <col className="w-[18%]" />
            <col className="w-[24%]" />
            <col className="w-[18%]" />
            <col className="w-[14%]" />
            <col className="w-[26%]" />
          </colgroup>
          <thead className="border-b border-slate-800 text-slate-300">
          <tr>
            <th className="px-4 py-3">
              <AdminSortHeaderButton field="name" label="Nombre" onSort={requestSort} sortBy={sortBy} sortDirection={sortDirection} />
            </th>
            <th className="px-4 py-3">
              <AdminSortHeaderButton field="email" label="Email" onSort={requestSort} sortBy={sortBy} sortDirection={sortDirection} />
            </th>
            <th className="px-4 py-3">
              <AdminSortHeaderButton field="role" label="Rol" onSort={requestSort} sortBy={sortBy} sortDirection={sortDirection} />
            </th>
            <th className="px-4 py-3">
              <AdminSortHeaderButton field="status" label="Estado" onSort={requestSort} sortBy={sortBy} sortDirection={sortDirection} />
            </th>
            <th className="px-4 py-3">Acciones</th>
          </tr>
          </thead>

          <tbody>
          {showLoadingRow && <AdminTableStateRow colSpan={5} message="Cargando usuarios..." />}
          {showErrorRow && <AdminTableStateRow colSpan={5} message={getApiErrorMessage(usersError)} tone="error" />}
          {showEmptyRow && <AdminTableStateRow colSpan={5} message="No hay usuarios para los filtros seleccionados." />}

          {showDataRows && users.map((user) => {
            const isSelfUser = currentUser?.id === user.id;
            const isBanFormVisible = activeBanUserId === user.id;
            const canModerate = !isSelfUser;

            return (
                <tr className="border-b border-slate-800/70 align-top transition-colors hover:bg-slate-800/30 last:border-0" key={user.id}>
                  <td className="truncate px-4 py-3 font-medium text-slate-100" title={user.name}>
                    {user.name}
                  </td>
                  <td className="truncate px-4 py-3 text-slate-300" title={user.email}>
                    {user.email}
                  </td>
                  <td className="truncate px-4 py-3 text-slate-300" title={user.role ?? 'user'}>
                    {user.role ?? 'user'}
                  </td>
                  <td className="truncate px-4 py-3 text-slate-300" title={user.ban_state_label ?? 'Activo'}>
                    {user.ban_state_label ?? 'Activo'}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      <Link
                          className="rounded-md border border-slate-700 px-2 py-1 text-xs font-medium text-slate-100 transition-colors hover:bg-slate-800"
                          to={`/admin/users/${user.id}`}
                      >
                        Ver detalle
                      </Link>

                      {canModerate && !user.is_banned && (
                          <button
                              className="rounded-md bg-red-700 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-60"
                              disabled={isMutating}
                              onClick={() => setActiveBanUserId(user.id)}
                              type="button"
                          >
                            Banear
                          </button>
                      )}

                      {canModerate && user.is_banned && (
                          <button
                              className="rounded-md bg-emerald-700 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                              disabled={isMutating}
                              onClick={() => unbanUser({ userId: user.id })}
                              type="button"
                          >
                            Desbanear
                          </button>
                      )}
                    </div>

                    {isBanFormVisible && (
                        <AdminUserBanForm
                            isMutating={isMutating}
                            onCancel={() => setActiveBanUserId(null)}
                            onConfirm={async ({ reason, isPermanent, days }) => {
                              await banUser({ userId: user.id, reason, isPermanent, days });
                              setActiveBanUserId(null);
                            }}
                        />
                    )}
                  </td>
                </tr>
            );
          })}
          </tbody>
        </table>
      </div>
  );
};