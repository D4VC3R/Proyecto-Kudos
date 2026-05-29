import React from 'react';
import { SearchX } from 'lucide-react';
// Componentes
import MyProposalsHeader from '../../components/proposals/MyProposalsHeader.jsx';
import MyProposalsEmpty from '../../components/proposals/MyProposalsEmpty.jsx';
import FeedbackState from "../../components/ui/FeedbackState.jsx";
import MyProposalItemCard from '../../components/proposals/MyProposalItemCard.jsx';
import MyProposalItemCardSkeleton from '../../components/proposals/MyProposalItemCardSkeleton.jsx';
import FadeUp from "../../components/animations/FadeUp.jsx";
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
// Hooks
import { useMyProposalsPage } from '../../hooks/pages/useMyProposalsPage.js';

const MyProposalsPage = () => {
  const { state, actions } = useMyProposalsPage();

  return (
    <div className="flex w-full flex-col relative">
      <FadeUp
        className={`flex flex-col gap-6 relative transition-opacity duration-200 ${state.isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}
      >
        <MyProposalsHeader
          meta={state.meta}
          filters={state.filters}
          updateParams={actions.updateParams}
        />

        {state.showSkeletons ? (
          <div className="grid gap-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <MyProposalItemCardSkeleton key={i} />
            ))}
          </div>
        ) : state.proposals.length === 0 ? (
          <MyProposalsEmpty />
        ) : state.filteredProposals.length === 0 ? (
          <FeedbackState
            icon={SearchX}
            iconColorClass="bg-slate-200 text-slate-500"
            title="No hay coincidencias"
            description="No encontramos propuestas que coincidan con tu búsqueda actual."
            customLayoutClass="rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-12 w-full"
          />
        ) : (
          <div className="grid gap-4">
            {state.filteredProposals.map((proposal) => (
              <MyProposalItemCard
                key={proposal.id}
                proposal={proposal}
                isDeleting={state.isDeleting && state.proposalToDelete?.id === proposal.id}
                onDeleteClick={actions.handleOpenDeleteModal}
                onEditClick={actions.handleEditClick}
              />
            ))}
          </div>
        )}
      </FadeUp>

      <Modal
        isOpen={!!state.proposalToDelete}
        onClose={actions.handleCloseDeleteModal}
        title="Eliminar Propuesta"
        footer={
          <ModalButtons
            onClose={actions.handleCloseDeleteModal}
            onConfirm={actions.handleConfirmDelete}
            isPending={state.isDeleting}
            confirmText="Sí, Eliminar"
            actionStyle="danger"
          />
        }
      >
        <p className="text-nav-item font-medium">
          ¿Estás seguro de que deseas eliminar la propuesta <strong>{state.proposalToDelete?.name}</strong>?
        </p>
      </Modal>
    </div>
  );
};

export default MyProposalsPage;