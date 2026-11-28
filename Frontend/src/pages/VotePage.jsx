import React from 'react';
import { AnimatePresence } from "framer-motion";
import { Loader2 } from 'lucide-react';
import clsx from 'clsx';
// Componentes
import CommentBox from '../components/comments/CommentBox';
import ItemDetailPoster from '../components/items/ItemDetailPoster.jsx';
import VoteActionButtons from '../components/votes/VoteActionButtons.jsx';
import SwipeVoteCard from '../components/votes/SwipeVoteCard.jsx';
import VoteKnowStep from '../components/votes/VoteKnowStep.jsx';
import VoteScoreLevels from '../components/votes/VoteScoreLevels.jsx';
import VoteInterestStep from '../components/votes/VoteInterestStep.jsx';
import VoteDoneStep from '../components/votes/VoteDoneStep.jsx';
import EmptyVoteState from "../components/votes/EmptyVoteState.jsx";
import AnimatedItem from "../components/animations/AnimatedItem.jsx";
import FeedbackState from "../components/ui/FeedbackState.jsx";
import KudosBadge from '../components/ui/KudosBadge.jsx';
// Hooks
import { useVotePage } from '../hooks/pages/useVotePage.js';
import { VOTE_STEPS } from '../lib/constants.js';
import { hasText } from '../lib/text.js';

export const VotePage = () => {
  const { state, actions } = useVotePage();
  const { item } = state;

  if (state.isLoading) {
    return (
      <div className="flex min-h-state items-center justify-center">
        <FeedbackState
          icon={Loader2}
          isLoading
          description="Buscando el siguiente candidato..."
        />
      </div>
    );
  }

  if (!item) return <EmptyVoteState />;

  const year = item.attributes?.year ?? item.released_at?.slice(0, 4);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-b border-line pb-4">
        <p className="text-caption font-semibold uppercase tracking-widest text-kicker">
          Votar · {item.category?.name}
        </p>
        <p className="flex items-center gap-2 text-small text-ink-muted">
          Cada voto suma kudos
          <KudosBadge value={state.kudos} />
        </p>
      </div>

      <AnimatePresence mode="wait">
        <AnimatedItem key={item.id} itemKey={item.id}>
          <div
            className={clsx(
              'grid w-full gap-6 pt-6 transition-opacity duration-300 md:grid-cols-vote md:items-start md:gap-10 lg:grid-cols-vote-lg lg:gap-14',
              state.isBackgroundUpdating && !state.isDone && 'opacity-50',
              state.isBusy && 'pointer-events-none',
            )}
          >
            <SwipeVoteCard
              enabled={state.step === VOTE_STEPS.KNOW && !state.isBusy && !state.isDone}
              onSwipeRight={actions.handleKnown}
              onSwipeLeft={actions.handleUnknown}
            >
              <ItemDetailPoster item={item} showCredit={false} />
            </SwipeVoteCard>

            <div className="min-w-0">
              <p className="text-caption font-semibold uppercase tracking-widest text-kicker">
                {item.category?.name}{year ? ` · ${year}` : ''}
              </p>
              <h1 className="mb-3 mt-2 font-display text-h1 text-ink">{item.name}</h1>
              {hasText(item.description) && (
                <p className="max-w-prose line-clamp-4 text-ink-muted">{item.description}</p>
              )}

              <section
                data-vote-question
                aria-live="polite"
                aria-label="Tu voto"
                className="mt-6 rounded-md border border-line bg-surface p-5"
              >
                {state.isDone ? (
                  <VoteDoneStep kudosAwarded={state.kudosAwarded} autoFocus={state.hasInteracted} />
                ) : (
                  <>
                    {state.step === VOTE_STEPS.KNOW && (
                      <VoteKnowStep
                        onYes={actions.handleKnown}
                        onNo={actions.handleUnknown}
                        isPending={state.isBusy}
                        autoFocus={state.hasInteracted}
                      />
                    )}
                    {state.step === VOTE_STEPS.RATE && (
                      <VoteScoreLevels
                        onRate={actions.handleVote}
                        onBack={actions.handleBack}
                        isPending={state.isBusy}
                        autoFocus={state.hasInteracted}
                      />
                    )}
                    {state.step === VOTE_STEPS.INTEREST && (
                      <VoteInterestStep
                        onWant={actions.handleWant}
                        onNotInterested={actions.handleNotInterested}
                        onBack={actions.handleBack}
                        isPending={state.isBusy}
                        autoFocus={state.hasInteracted}
                      />
                    )}
                  </>
                )}
              </section>

              <VoteActionButtons
                itemUrl={`/${state.categorySlug}/item/${item.id}`}
                showComments={state.showComments}
                onToggleComments={actions.toggleComments}
                commentsId="vote-comments"
              />

              {state.showComments && (
                <div id="vote-comments" className="mt-6 border-t border-line pt-6">
                  <CommentBox itemId={item.id} />
                </div>
              )}
            </div>
          </div>
        </AnimatedItem>
      </AnimatePresence>
    </div>
  );
};

export default VotePage;
