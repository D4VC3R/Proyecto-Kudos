import React, { useState } from 'react';
import { FileText, Trash2, Edit3, Clock } from 'lucide-react';
import { Modal } from '../common/Modal';
import { PROPOSAL_STATUS_CONFIG } from '../../lib/constants';

export const MyProposalItemCard = ({ proposal, isDeleting, onDelete }) => {
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);

  const StateIcon = PROPOSAL_STATUS_CONFIG[proposal.status]?.icon || Clock;
  const stateColor = PROPOSAL_STATUS_CONFIG[proposal.status]?.color || 'bg-slate-100 text-slate-800';
  const stateLabel = PROPOSAL_STATUS_CONFIG[proposal.status]?.label || 'Desconocido';

  const handleDeleteConfirm = () => {
    onDelete(proposal.id);
    setIsDeleteModalOpen(false);
  };

  return (
    <>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
        <div className="flex items-start gap-4">
          <div className={`mt-1 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100`}>
            {proposal.images && proposal.images.length > 0 ? (
              <img
                src={`${import.meta.env.VITE_API_URL?.replace('/api', '')}/storage/${proposal.images[0].path}`}
                alt={proposal.name}
                className="h-full w-full rounded-xl object-cover"
                onError={(e) => {
                  e.target.style.display = 'none';
                  e.target.nextSibling.style.display = 'flex';
                }}
              />
            ) : null}
            <div className="flex h-full w-full items-center justify-center text-slate-400" style={{ display: proposal.images?.length > 0 ? 'none' : 'flex' }}>
              <FileText size={24} />
            </div>
          </div>

          <div className="flex flex-col">
            <div className="flex items-center gap-3">
              <h4 className="text-lg font-bold text-slate-900">{proposal.name}</h4>
              <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold border ${stateColor}`}>
                <StateIcon size={12} strokeWidth={3} />
                {stateLabel}
              </span>
            </div>
            <p className="text-sm font-medium text-blue-600 mt-0.5">{proposal.category?.name || 'Categoría'}</p>

            {proposal.status === 'rejected' && proposal.admin_notes && (
              <div className="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 border border-red-100">
                <span className="font-bold block mb-1">Nota del Administrador:</span>
                {proposal.admin_notes}
              </div>
            )}

            {proposal.status === 'changes_requested' && proposal.admin_notes && (
              <div className="mt-3 rounded-xl bg-orange-50 p-3 text-sm text-orange-800 border border-orange-100">
                <span className="font-bold block mb-1">Se requieren cambios:</span>
                {proposal.admin_notes}
              </div>
            )}
          </div>
        </div>

        <div className="flex items-center gap-2 self-end sm:self-center">
          {(proposal.status === 'changes_requested' || proposal.status === 'pending') && (
             <button
               title="Editar Propuesta"
               onClick={() => setIsEditModalOpen(true)}
               className="flex items-center justify-center h-10 w-10 rounded-xl bg-slate-50 text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition-colors"
             >
               <Edit3 size={18} />
             </button>
          )}

          {(proposal.status === 'pending' || proposal.status === 'rejected') && (
             <button
               title="Eliminar Propuesta"
               onClick={() => setIsDeleteModalOpen(true)}
               disabled={isDeleting}
               className="flex items-center justify-center h-10 w-10 rounded-xl bg-slate-50 text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors disabled:opacity-50"
             >
               <Trash2 size={18} />
             </button>
          )}
        </div>
      </div>

      <Modal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        title="Eliminar Propuesta"
        footer={
          <>
            <button
              onClick={() => setIsDeleteModalOpen(false)}
              className="rounded-xl px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200 transition-colors"
            >
              Cancelar
            </button>
            <button
              onClick={handleDeleteConfirm}
              className="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 transition-colors shadow-md border border-red-700"
            >
              Sí, Eliminar
            </button>
          </>
        }
      >
        <p className="text-slate-600 font-medium">
          ¿Estás seguro de que deseas eliminar la propuesta <strong>{proposal.name}</strong>? Esta acción no se puede deshacer.
        </p>
      </Modal>

      <Modal
        isOpen={isEditModalOpen}
        onClose={() => setIsEditModalOpen(false)}
        title="Editar Propuesta"
        footer={
          <button
            onClick={() => setIsEditModalOpen(false)}
            className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 transition-colors shadow-md"
          >
            Entendido
          </button>
        }
      >
        <p className="text-slate-600 font-medium">
          La función de edición completa está en desarrollo. ¡Pronto podrás realizar cambios a tu propuesta sin necesidad de cancelarla!
        </p>
      </Modal>
    </>
  );
};
