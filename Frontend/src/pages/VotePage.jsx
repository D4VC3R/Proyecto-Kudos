import React from 'react';
import { AnimatePresence } from "framer-motion";
import { Loader2 } from 'lucide-react';
// Componentes
import VoteStars from '../components/votes/VoteStars.jsx';
import CommentBox from '../components/comments/CommentBox';
import ItemImage from '../components/items/ItemImage.jsx';
import VoteActionButtons from '../components/votes/VoteActionButtons.jsx';
import EmptyVoteState from "../components/votes/EmptyVoteState.jsx";
import AnimatedItem from "../components/animations/AnimatedItem.jsx";
import BackButton from "../components/ui/BackButton.jsx";
import FeedbackState from "../components/ui/FeedbackState.jsx";
// Hooks
import { useVotePage } from '../hooks/pages/useVotePage.js';

export const VotePage = () => {
  const { state, actions } = useVotePage();
  const showControls = !state.isLoading && state.item;

  return (
    <div className="flex flex-col w-full max-w-3xl mx-auto pb-6 lg:pb-0 lg:h-page-content">
      <div className="shrink-0 mb-4 px-2">
        <BackButton />
      </div>

      <div
        className={`flex flex-col flex-1 min-h-0 transition-opacity duration-300 ${
          state.isBackgroundUpdating ? 'opacity-50 pointer-events-none' : 'opacity-100'
        }`}
      >
        <div className="w-full h-full bg-surface rounded-3xl p-4 md:p-6 shadow-xl ring-1 ring-slate-200 flex flex-col relative overflow-hidden">
          <div className={`flex-1 min-h-0 flex justify-center items-center w-full ${showControls ? 'mb-6' : ''}`}>
            {state.isLoading ? (
              <FeedbackState
                icon={Loader2}
                isLoading
                description="Buscando el siguiente candidato..."
              />
            ) : !state.item ? (
              <EmptyVoteState category={state.categorySlug} />
            ) : (
              <AnimatePresence mode="wait">
                <AnimatedItem
                  key={state.item.id}
                  itemKey={state.item.id}
                  className="w-full h-full flex justify-center items-center"
                >
                  <ItemImage item={state.item} />
                </AnimatedItem>
              </AnimatePresence>
            )}
          </div>
          {showControls && (
            <>
              <div className="shrink-0 mb-1 z-10 relative">
                <VoteStars onVote={actions.handleVote} isPending={state.isPending} />
              </div>

              <div className="shrink-0">
                <VoteActionButtons
                  onSkip={actions.handleSkip}
                  showComments={state.showComments}
                  onToggleComments={actions.toggleComments}
                />
              </div>

              {state.showComments && (
                <div className="mt-4 pt-4 border-t border-slate-100 shrink-0 flex flex-col max-h-[35vh]">
                  <CommentBox itemId={state.item.id} className="h-full" />
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default VotePage;