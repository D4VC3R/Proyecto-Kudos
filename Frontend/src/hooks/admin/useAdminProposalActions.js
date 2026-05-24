import { useState } from 'react';
import { useModal } from '../common/useModal';
import { useReviewProposal } from './useAdminProposalMutations';

/**
 * Controlador de dominio para la revisión de propuestas.
 * Aísla la lógica de aprobación/rechazo y la gestión de notas del moderador.
 */
export const useAdminProposalActions = () => {
  const { isOpen, modalType: actionType, modalData: selectedProposal, openModal, closeModal } = useModal();

  const [adminNotes, setAdminNotes] = useState('');

  const { mutate: reviewProposal, isPending } = useReviewProposal();

  // Envolvemos el openModal nativo para garantizar que no haya notas residuales
  // de una propuesta anterior al abrir una nueva.
  const handleOpenAction = (proposal, type) => {
    openModal(type, proposal);
    setAdminNotes('');
  };

  const executeAction = () => {
    if (!selectedProposal) return;

    reviewProposal({
      id: selectedProposal.id,
      status: actionType,
      admin_notes: adminNotes
    }, { onSuccess: closeModal });
  };

  return {
    isOpen,
    actionType,
    selectedProposal,
    closeModal,

    handleOpenAction,
    executeAction,

    isPending,

    adminNotes,
    setAdminNotes,
  };
};