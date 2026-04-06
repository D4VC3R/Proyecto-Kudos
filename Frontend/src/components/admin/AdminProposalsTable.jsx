import { useState } from 'react';
import { useAdminProposalsContext } from '../../hooks/admin';
import { AdminProposalReviewForm } from './AdminProposalReviewForm';
import { AdminTableStateRow } from './shared/AdminTableStateRow';
import { getApiErrorMessage } from '../../lib/apiErrorMap';

export const AdminProposalsTable = () => {
  const { proposals, isLoadingProposals, isProposalsError, proposalsError, isMutatingProposals, reviewProposal } = useAdminProposalsContext();
  const [activeReviewProposalId, setActiveReviewProposalId] = useState(null);

  const showLoadingRow = isLoadingProposals;
  const showErrorRow = isProposalsError;
  const showEmptyRow = !isLoadingProposals && !isProposalsError && proposals.length === 0;
  const showDataRows = !isLoadingProposals && !isProposalsError && proposals.length > 0;

  const formatUserName = (proposal) => proposal?.creator?.name ?? 'Sin creador';
  const formatCategoryName = (proposal) => proposal?.category?.name ?? 'Sin categoria';

  return (
      <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
        <table className="min-w-full text-left text-sm">
          <thead className="border-b border-slate-800 text-slate-300">
          <tr>
            <th className="px-4 py-3">Propuesta</th>
            <th className="px-4 py-3">Categoria</th>
            <th className="px-4 py-3">Creador</th>
            <th className="px-4 py-3">Estado</th>
            <th className="px-4 py-3">Accion</th>
          </tr>
          </thead>

          <tbody>
          {showLoadingRow && <AdminTableStateRow colSpan={5} message="Cargando propuestas..." />}
          {showErrorRow && <AdminTableStateRow colSpan={5} message={getApiErrorMessage(proposalsError)} tone="error" />}
          {showEmptyRow && <AdminTableStateRow colSpan={5} message="No hay propuestas para los filtros seleccionados." />}

          {showDataRows && proposals.map((proposal) => {
            const isReviewFormVisible = activeReviewProposalId === proposal.id;

            return (
                <tr className="border-b border-slate-800/70 align-top transition-colors hover:bg-slate-800/30 last:border-0" key={proposal.id}>
                  <td className="px-4 py-3">
                    <p className="font-medium text-slate-100">{proposal.name}</p>
                    <p className="line-clamp-2 text-xs text-slate-400">{proposal.description}</p>
                  </td>
                  <td className="px-4 py-3 text-slate-300">{formatCategoryName(proposal)}</td>
                  <td className="px-4 py-3 text-slate-300">{formatUserName(proposal)}</td>
                  <td className="px-4 py-3 text-slate-300">{proposal.status}</td>
                  <td className="px-4 py-3">
                    <button
                        className="rounded-md bg-indigo-600 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        disabled={isMutatingProposals}
                        onClick={() => setActiveReviewProposalId(proposal.id)}
                        type="button"
                    >
                      Revisar
                    </button>
                    {isReviewFormVisible && (
                        <AdminProposalReviewForm
                            isMutating={isMutatingProposals}
                            onCancel={() => setActiveReviewProposalId(null)}
                            onConfirm={async ({ status, adminNotes }) => {
                              await reviewProposal({ proposalId: proposal.id, status, adminNotes });
                              setActiveReviewProposalId(null);
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