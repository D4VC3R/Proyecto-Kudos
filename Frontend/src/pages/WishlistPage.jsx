import React from 'react';
import { Link } from 'react-router-dom';
import { Bookmark, Loader2 } from 'lucide-react';
// Componentes
import PageHeader from '../components/ui/PageHeader.jsx';
import SectionHead from '../components/ui/SectionHead.jsx';
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

  const renderSection = (id, title, votes) => votes.length > 0 && (
    <section aria-labelledby={id} className="flex flex-col gap-4">
      <SectionHead id={id} title={title}>
        <span className="text-small tabular-nums text-ink-muted">{votes.length}</span>
      </SectionHead>
      <ul className="grid gap-3">
        {votes.map((vote) => (
          <li key={vote.id}>
            <WishlistItemCard
              vote={vote}
              onRateClick={actions.handleOpenRate}
              onRemoveClick={actions.handleRemove}
              isRemoving={state.removingId === vote.id}
              isBusy={state.isUpdating}
            />
          </li>
        ))}
      </ul>
    </section>
  );

  return (
    <div className="flex w-full flex-col gap-8">
      <PageHeader
        title="Tu"
        highlight="Wishlist"
        subtitle="Lo que te interesa y aún no has visto o jugado."
      >
        <p className="text-small text-ink-muted">
          <span className="font-semibold tabular-nums text-ink">{state.total}</span> en tu lista
        </p>
      </PageHeader>

      <FadeUp className="flex w-full max-w-4xl flex-col gap-8">
        {state.showSkeletons ? (
          <div className="grid gap-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <MyVoteItemCardSkeleton key={i}/>
            ))}
          </div>
        ) : state.isEmpty ? (
          <FeedbackState
            icon={Bookmark}
            title="Tu wishlist está vacía"
            titleAs="h2"
            description='Cuando votes "No lo conozco" → "Me interesa", el ítem aparecerá aquí.'
            customLayoutClass="rounded-md border border-line bg-surface p-8 sm:p-12"
          >
            <Button as={Link} to="/" variant="primary" size="lg">
              Ir a votar
            </Button>
          </FeedbackState>
        ) : (
          <>
            {renderSection('wishlist-available', 'Ya disponible', state.available)}
            {renderSection('wishlist-upcoming', 'Próximamente', state.upcoming)}

            <div ref={refs.lastElementRef} className="flex h-12 w-full items-center justify-center py-4">
              {state.isFetchingNextPage && <Loader2 aria-hidden="true" className="animate-spin text-accent" size={24}/>}
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
