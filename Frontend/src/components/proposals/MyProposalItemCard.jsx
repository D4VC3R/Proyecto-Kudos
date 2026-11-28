import React from 'react';
import clsx from 'clsx';
import { FileText, Trash2, Edit3, Clock } from 'lucide-react';
import { PROPOSAL_STATUS_CONFIG } from '../../lib/constants';
// Componentes
import Button from '../ui/Button.jsx';
import Chip from '../ui/Chip.jsx';
import StorageImage from '../ui/StorageImage.jsx';

// Tarjeta de una propuesta propia: solo pinta; editar y borrar llegan por props.
const MyProposalItemCard = ({ proposal, isDeleting, onDeleteClick, onEditClick }) => {
  const StateIcon = PROPOSAL_STATUS_CONFIG[proposal.status]?.icon || Clock;
  const stateLabel = PROPOSAL_STATUS_CONFIG[proposal.status]?.label || 'Desconocido';
  const hasNotes = proposal.admin_notes && (proposal.status === 'rejected' || proposal.status === 'changes_requested');
  const isRejected = proposal.status === 'rejected';

  return (
    <article data-my-proposal-card className="flex flex-col gap-4 rounded-md border border-line bg-surface p-4 sm:flex-row sm:items-center">
      <div className="flex min-w-0 flex-1 items-start gap-4">
        <span className="block w-12 shrink-0 overflow-hidden rounded-sm border border-line bg-surface aspect-poster">
          <StorageImage
            src={proposal.images?.[0]?.variants?.thumb}
            alt=""
            width={200}
            height={300}
            className="h-full w-full object-cover"
            fallbackIcon={FileText}
          />
        </span>

        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="font-display text-h3 text-ink line-clamp-2">{proposal.name}</h3>
            <Chip>
              <StateIcon size={12} aria-hidden="true" className="mr-1" />
              {stateLabel}
            </Chip>
          </div>
          <p className="mt-1 text-small text-ink-muted">{proposal.category?.name || 'Categoría'}</p>

          {hasNotes && (
            <div
              className={clsx(
                'mt-3 rounded-sm border p-3 text-small text-ink',
                isRejected ? 'border-danger/40' : 'border-warning/60',
              )}
            >
              <span className="mb-1 block font-semibold">
                {isRejected ? 'Nota del Administrador:' : 'Se requieren cambios:'}
              </span>
              {proposal.admin_notes}
            </div>
          )}
        </div>
      </div>

      <div className="flex flex-wrap gap-2 sm:shrink-0">
        {(proposal.status === 'changes_requested' || proposal.status === 'pending') && (
          <Button
            variant="ghost"
            icon={Edit3}
            onClick={() => onEditClick(proposal)}
            aria-label={`Editar «${proposal.name}»`}
          >
            Editar
          </Button>
        )}

        {(proposal.status === 'pending' || proposal.status === 'rejected') && (
          <Button
            variant="ghost"
            icon={Trash2}
            className="text-danger"
            onClick={() => onDeleteClick(proposal)}
            isLoading={isDeleting}
            aria-label={`Eliminar «${proposal.name}»`}
          >
            Eliminar
          </Button>
        )}
      </div>
    </article>
  );
};

export default MyProposalItemCard;
