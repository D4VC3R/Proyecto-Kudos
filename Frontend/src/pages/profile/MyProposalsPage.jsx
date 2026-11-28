import React from 'react';
import {SearchX} from 'lucide-react';
// Componentes
import MyProposalsHeader from '../../components/proposals/MyProposalsHeader.jsx';
import MyProposalsEmpty from '../../components/proposals/MyProposalsEmpty.jsx';
import FeedbackState from "../../components/ui/FeedbackState.jsx";
import MyProposalItemCard from '../../components/proposals/MyProposalItemCard.jsx';
import MyProposalItemCardSkeleton from '../../components/ui/skeletons/MyProposalItemCardSkeleton.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
// Hooks
import {useMyProposalsPage} from '../../hooks/pages/useMyProposalsPage.js';

const MyProposalsPage = () => {
  const {state, actions} = useMyProposalsPage();

  return (
    <div className="flex w-full flex-col relative">
      <div className="relative flex flex-col gap-6">
        <MyProposalsHeader
          meta={state.meta}
          filters={state.filters}
          updateParams={actions.updateParams}
        />

        {state.showSkeletons ? (
          <div className="grid gap-4">
            {Array.from({length: 4}).map((_, i) => (
              <MyProposalItemCardSkeleton key={i}/>
            ))}
          </div>
        ) : state.proposals.length === 0 ? (
          <MyProposalsEmpty/>
        ) : state.filteredProposals.length === 0 ? (
          <FeedbackState
            icon={SearchX}
            titleAs="h3"
            title="No hay coincidencias"
            description="No encontramos propuestas que coincidan con tu búsqueda actual."
            customLayoutClass="rounded-md border border-dashed border-line p-12"
          />
        ) : (
          <ul className="grid gap-4">
            {state.filteredProposals.map((proposal) => (
              <li key={proposal.id}>
                <MyProposalItemCard
                  proposal={proposal}
                  isDeleting={state.isDeleting && state.proposalToDelete?.id === proposal.id}
                  onDeleteClick={actions.handleOpenDeleteModal}
                  onEditClick={actions.handleEditClick}
                />
              </li>
            ))}
          </ul>
        )}
      </div>

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
        <p className="text-ink">
          ¿Estás seguro de que deseas eliminar la propuesta <strong>{state.proposalToDelete?.name}</strong>?
        </p>
      </Modal>
    </div>
  );
};

export default MyProposalsPage;