import React from 'react';
import { Link } from 'react-router-dom';
import { Bookmark, Loader2 } from 'lucide-react';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import Button from '../components/ui/Button.jsx';
import FadeUp from '../components/animations/FadeUp.jsx';
import Modal from '../components/ui/Modal.jsx';
import MyVoteItemCardSkeleton from '../components/ui/skeletons/MyVoteItemCardSkeleton.jsx';
import WishlistItemCard from '../components/votes/WishlistItemCard.jsx';
import VoteScoreLevels from '../components/votes/VoteScoreLevels.jsx';
// Hooks
import { useWishlistPage } from '../hooks/pages/useWishlistPage.js';

const WishlistPage = () => {
  const { state, actions, refs } = useWishlistPage();

  const renderSection = (title, votes) => votes.length > 0 && (
    <section className="flex flex-col gap-4">
      <h2 className="flex items-center gap-2 text-xl font-black text-text-highlight">
        {title}
        <span className="rounded-full bg-background px-2.5 py-0.5 text-sm font-bold text-slate-400">{votes.length}</span>
      </h2>
      <div className="grid gap-4">
        {votes.map((vote) => (
          <WishlistItemCard
            key={vote.id}
            vote={vote}
            onRateClick={actions.handleOpenRate}
            onRemoveClick={actions.handleRemove}
            isRemoving={state.removingId === vote.id}
            isBusy={state.isUpdating}
          />
        ))}
      </div>
    </section>
  );

  return (
    <div className="flex w-full flex-col">
      <div className="mb-8 shrink-0">
        <SectionHeader
          size="large"
          title="Tu"
          highlight="Wishlist"
          subtitle="Lo que te interesa y aún no has visto o jugado."
        >
          <div className="flex flex-col items-center justify-center p-2 bg-background rounded-xl min-w-[70px]">
            <span className="text-xl text-text-highlight font-black">{state.total}</span>
            <span className="text-[10px] uppercase text-slate-400 font-bold">En tu lista</span>
          </div>
        </SectionHeader>
      </div>

      <FadeUp className="mx-auto flex w-full max-w-4xl flex-col gap-8">
        {state.showSkeletons ? (
          <div className="grid gap-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <MyVoteItemCardSkeleton key={i}/>
            ))}
          </div>
        ) : state.isEmpty ? (
          <FadeUp>
            <FeedbackState
              icon={Bookmark}
              iconColorClass="h-20 w-20 rounded-3xl bg-blue-50 text-blue-500"
              title="Tu wishlist está vacía"
              description='Cuando votes "No lo conozco" → "Me interesa", el ítem aparecerá aquí.'
              customLayoutClass="border border-border shadow-sm bg-surface rounded-3xl p-12"
            >
              <Button as={Link} to="/" variant="primary" size="lg">
                Ir a votar
              </Button>
            </FeedbackState>
          </FadeUp>
        ) : (
          <>
            {renderSection('Ya disponible', state.available)}
            {renderSection('Próximamente', state.upcoming)}

            <div ref={refs.lastElementRef} className="flex h-12 w-full items-center justify-center py-4">
              {state.isFetchingNextPage && <Loader2 className="animate-spin text-blue-500" size={24}/>}
            </div>
          </>
        )}
      </FadeUp>

      <Modal
        isOpen={!!state.voteToRate}
        onClose={actions.handleCloseRate}
        title={state.voteToRate?.item?.name}
      >
        <VoteScoreLevels
          onRate={actions.handleRate}
          isPending={state.isUpdating}
          showKeys={false}
          stacked
        />
      </Modal>
    </div>
  );
};

export default WishlistPage;
