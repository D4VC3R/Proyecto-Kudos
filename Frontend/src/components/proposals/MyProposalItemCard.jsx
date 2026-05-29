import React from 'react';
import { FileText, Trash2, Edit3, Clock } from 'lucide-react';
import { PROPOSAL_STATUS_CONFIG } from '../../lib/constants';
// Componentes
import Button from '../ui/Button.jsx';
import StorageImage from '../ui/StorageImage.jsx';

const MyProposalItemCard = ({ proposal, isDeleting, onDeleteClick, onEditClick }) => {
  const StateIcon = PROPOSAL_STATUS_CONFIG[proposal.status]?.icon || Clock;
  const stateColor = PROPOSAL_STATUS_CONFIG[proposal.status]?.color || 'bg-slate-100 text-slate-800';
  const stateLabel = PROPOSAL_STATUS_CONFIG[proposal.status]?.label || 'Desconocido';

  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-surface p-5 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
      <div className="flex items-start gap-4">
        <div className="mt-1 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl overflow-hidden bg-slate-100">
          <StorageImage
            src={proposal.images?.[0]?.path}
            alt={proposal.name || 'Propuesta'}
            className="h-full w-full"
            fallbackIcon={FileText}
          />
        </div>

        <div className="flex flex-col">
          <div className="flex items-center gap-3">
            <h4 className="text-lg font-bold text-text-highlight">{proposal.name}</h4>
            <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold border ${stateColor}`}>
              <StateIcon size={12} strokeWidth={3} />
              {stateLabel}
            </span>
          </div>
          <p className="text-sm font-medium text-primary mt-0.5">{proposal.category?.name || 'Categoría'}</p>

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
          <Button
            title="Editar Propuesta"
            onClick={() => onEditClick(proposal)}
            variant="ghost"
            color="primary"
            size="iconLg"
            icon={Edit3}
          />
        )}

        {(proposal.status === 'pending' || proposal.status === 'rejected') && (
          <Button
            title="Eliminar Propuesta"
            onClick={() => onDeleteClick(proposal)}
            isLoading={isDeleting}
            variant="ghost"
            color="danger"
            size="iconLg"
            icon={Trash2}
          />
        )}
      </div>
    </div>
  );
};

export default MyProposalItemCard;