import React from 'react';
import { Loader2 } from 'lucide-react';
// Componentes
import MyVotesHeader from '../../components/votes/MyVotesHeader.jsx';
import MyVotesEmpty from '../../components/votes/MyVotesEmpty.jsx';
import MyVoteItemCard from '../../components/votes/MyVoteItemCard.jsx';
import MyVoteItemCardSkeleton from '../../components/votes/MyVoteItemCardSkeleton.jsx';
import FadeUp from "../../components/animations/FadeUp.jsx";
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from '../../components/ui/ModalButtons.jsx';
// Hooks
import { useMyVotesPage } from '../../hooks/pages/useMyVotesPage.js';

const MyVotesPage = () => {
  const { state, actions, refs } = useMyVotesPage();

  return (
    <div className="flex w-full flex-col relative">
      <FadeUp
        className={`flex flex-col gap-6 relative transition-opacity duration-200 ${state.isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}>

        <MyVotesHeader
          meta={state.meta}
          currentView={state.currentView}
          currentCategory={state.currentCategory}
          updateParams={actions.updateParams}
        />

        {state.showSkeletons ? (
          <div className="grid gap-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <MyVoteItemCardSkeleton key={i} />
            ))}
          </div>
        ) : state.allVotes.length === 0 ? (
          <MyVotesEmpty />
        ) : (
          <>
            <div className="grid gap-4">
              {state.allVotes.map((vote) => (
                <MyVoteItemCard
                  key={vote.id}
                  vote={vote}
                  isDeleting={state.isDeleting && state.voteToDelete?.id === vote.id}
                  onDeleteClick={actions.handleOpenDeleteModal}
                  isUpdating={state.isUpdating}
                  onUpdate={actions.updateVote}
                />
              ))}
            </div>

            <div ref={refs.lastElementRef} className="flex h-12 w-full items-center justify-center py-4">
              {state.isFetchingNextPage && <Loader2 className="animate-spin text-blue-500" size={24} />}
            </div>

            {!state.hasNextPage && state.allVotes.length > 0 && (
              <div className="text-center py-4 text-slate-400 font-medium text-sm">
                Has llegado al final de tu historial.
              </div>
            )}
          </>
        )}
      </FadeUp>

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
        <p className="text-nav-item font-medium">
          ¿Estás seguro de que deseas eliminar este registro de voto para <strong>{state.voteToDelete?.item?.name}</strong>?
        </p>
      </Modal>
    </div>
  );
};

export default MyVotesPage;