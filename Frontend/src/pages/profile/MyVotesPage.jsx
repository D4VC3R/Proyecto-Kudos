import React from 'react';
import {Loader2} from 'lucide-react';
// Componentes
import MyVotesHeader from '../../components/votes/MyVotesHeader.jsx';
import MyVotesEmpty from '../../components/votes/MyVotesEmpty.jsx';
import MyVoteItemCard from '../../components/votes/MyVoteItemCard.jsx';
import MyVoteItemCardSkeleton from '../../components/ui/skeletons/MyVoteItemCardSkeleton.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from '../../components/ui/ModalButtons.jsx';
// Hooks
import {useMyVotesPage} from '../../hooks/pages/useMyVotesPage.js';

const MyVotesPage = () => {
  const {state, actions, refs} = useMyVotesPage();

  return (
    <div className="flex w-full flex-col relative">
      <div className="relative flex flex-col gap-6">

        <MyVotesHeader
          meta={state.meta}
          currentView={state.currentView}
          currentCategory={state.currentCategory}
          updateParams={actions.updateParams}
        />

        {state.showSkeletons ? (
          <div className="grid gap-4">
            {Array.from({length: 4}).map((_, i) => (
              <MyVoteItemCardSkeleton key={i}/>
            ))}
          </div>
        ) : state.allVotes.length === 0 ? (
          <MyVotesEmpty/>
        ) : (
          <>
            <ul className="grid gap-4">
              {state.allVotes.map((vote) => (
                <li key={vote.id}>
                  <MyVoteItemCard
                    vote={vote}
                    isDeleting={state.isDeleting && state.voteToDelete?.id === vote.id}
                    onDeleteClick={actions.handleOpenDeleteModal}
                    isUpdating={state.isUpdating}
                    onUpdate={actions.updateVote}
                  />
                </li>
              ))}
            </ul>

            <div ref={refs.lastElementRef} className="flex h-12 w-full items-center justify-center py-4">
              {state.isFetchingNextPage && <Loader2 aria-hidden="true" className="animate-spin text-accent" size={24}/>}
            </div>

            {!state.hasNextPage && state.allVotes.length > 0 && (
              <div className="py-4 text-center text-small text-ink-muted">
                Has llegado al final de tu historial.
              </div>
            )}
          </>
        )}
      </div>

      <Modal
        isOpen={!!state.voteToDelete}
        onClose={actions.handleCloseDeleteModal}
        title="Eliminar voto"
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
          ¿Hacer como si nunca hubieras votado <strong>{state.voteToDelete?.item?.name}</strong>?
          No perderás Kudos por esta acción, pero volver a votar este elemento tampoco te dará más puntos.
        </p>
      </Modal>
    </div>
  );
};

export default MyVotesPage;