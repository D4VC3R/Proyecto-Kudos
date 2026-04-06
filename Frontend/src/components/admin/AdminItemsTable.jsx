import { useState } from 'react';
import { useAdminItemsContext } from '../../hooks/admin';
import { AdminSortHeaderButton } from './shared/AdminSortHeaderButton';
import { AdminTableStateRow } from './shared/AdminTableStateRow';
import { getApiErrorMessage } from '../../lib/apiErrorMap';

export const AdminItemsTable = () => {
  const { items, isLoadingItems, isItemsError, itemsError, isMutatingItems, moderateItemStatus, sortBy, sortDirection, requestSort } = useAdminItemsContext();
  const [editingItemId, setEditingItemId] = useState(null);
  const [reasonInput, setReasonInput] = useState('');
  const [reasonError, setReasonError] = useState('');

  const showLoadingRow = isLoadingItems;
  const showErrorRow = isItemsError;
  const showEmptyRow = !isLoadingItems && !isItemsError && items.length === 0;
  const showDataRows = !isLoadingItems && !isItemsError && items.length > 0;

  const formatCategory = (item) => item?.category?.name ?? 'Sin categoria';
  const formatCreator = (item) => item?.creator?.name ?? 'Sin creador';

  const getToggleAction = (item) => {
    return item?.status === 'active'
        ? { nextStatus: 'inactive', label: 'Desactivar', color: 'bg-red-700 hover:bg-red-600' }
        : { nextStatus: 'active', label: 'Activar', color: 'bg-emerald-700 hover:bg-emerald-600' };
  };

  const startDisableFlow = (itemId) => {
    setEditingItemId(itemId);
    setReasonInput('');
    setReasonError('');
  };

  const closeDisableFlow = () => {
    setEditingItemId(null);
    setReasonInput('');
    setReasonError('');
  };

  const submitDisable = async (itemId) => {
    const trimmedReason = reasonInput.trim();
    if (!trimmedReason) {
      setReasonError('El motivo es obligatorio al desactivar un item.');
      return;
    }

    const result = await moderateItemStatus({ itemId, status: 'inactive', reason: trimmedReason });
    if (result.ok) {
      closeDisableFlow();
      return;
    }
    setReasonError(result.validation?.reason?.[0] ?? 'No se pudo desactivar el item.');
  };

  return (
      <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table className="min-w-full text-left text-sm">
          <thead className="border-b border-slate-800 text-slate-300">
          <tr>
            <th className="px-4 py-3">
              <AdminSortHeaderButton field="name" inactiveIndicator="" label="Item" onSort={requestSort} sortBy={sortBy} sortDirection={sortDirection} />
            </th>
            <th className="px-4 py-3">Categoria</th>
            <th className="px-4 py-3">Creador</th>
            <th className="px-4 py-3">
              <AdminSortHeaderButton field="status" inactiveIndicator="" label="Estado" onSort={requestSort} sortBy={sortBy} sortDirection={sortDirection} />
            </th>
            <th className="px-4 py-3">Accion</th>
          </tr>
          </thead>

          <tbody>
          {showLoadingRow && <AdminTableStateRow colSpan={5} message="Cargando items..." />}
          {showErrorRow && <AdminTableStateRow colSpan={5} message={getApiErrorMessage(itemsError)} tone="error" />}
          {showEmptyRow && <AdminTableStateRow colSpan={5} message="No hay items para los filtros seleccionados." />}

          {showDataRows && items.map((item) => {
            const toggleAction = getToggleAction(item);

            return (
                <tr className="border-b border-slate-800/70 align-top transition-colors hover:bg-slate-800/30 last:border-0" key={item.id}>
                  <td className="px-4 py-3">
                    <p className="font-medium text-slate-100">{item.name}</p>
                    <p className="text-xs text-slate-400">{item.id}</p>
                  </td>
                  <td className="px-4 py-3 text-slate-300">{formatCategory(item)}</td>
                  <td className="px-4 py-3 text-slate-300">{formatCreator(item)}</td>
                  <td className="px-4 py-3 text-slate-300">{item.status}</td>
                  <td className="space-y-2 px-4 py-3">
                    {toggleAction.nextStatus === 'active' && (
                        <button
                            className={`rounded-md px-3 py-1 text-xs font-medium text-white transition-colors disabled:cursor-not-allowed disabled:opacity-60 ${toggleAction.color}`}
                            disabled={isMutatingItems}
                            onClick={() => moderateItemStatus({ itemId: item.id, status: 'active', reason: null })}
                            type="button"
                        >
                          {toggleAction.label}
                        </button>
                    )}

                    {toggleAction.nextStatus === 'inactive' && editingItemId !== item.id && (
                        <button
                            className={`rounded-md px-3 py-1 text-xs font-medium text-white transition-colors disabled:cursor-not-allowed disabled:opacity-60 ${toggleAction.color}`}
                            disabled={isMutatingItems}
                            onClick={() => startDisableFlow(item.id)}
                            type="button"
                        >
                          {toggleAction.label}
                        </button>
                    )}

                    {toggleAction.nextStatus === 'inactive' && editingItemId === item.id && (
                        <div className="space-y-2 rounded-md border border-slate-700 bg-slate-950 p-2">
                      <textarea
                          className="min-h-16 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-xs text-slate-100"
                          onChange={(event) => {
                            setReasonInput(event.target.value);
                            if (reasonError) setReasonError('');
                          }}
                          placeholder="Motivo de desactivacion (obligatorio)"
                          value={reasonInput}
                      />
                          {reasonError && <p className="text-xs text-red-300">{reasonError}</p>}
                          <div className="flex gap-2">
                            <button
                                className="rounded-md bg-red-700 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-60"
                                disabled={isMutatingItems}
                                onClick={() => submitDisable(item.id)}
                                type="button"
                            >
                              Confirmar
                            </button>
                            <button
                                className="rounded-md border border-slate-700 px-2 py-1 text-xs font-medium text-slate-200 transition-colors hover:bg-slate-800"
                                onClick={closeDisableFlow}
                                type="button"
                            >
                              Cancelar
                            </button>
                          </div>
                        </div>
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