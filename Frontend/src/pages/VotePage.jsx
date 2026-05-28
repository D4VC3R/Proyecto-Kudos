import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { useNextCategoryItem } from '../hooks/categories/useCategoryQueries';
import { useCreateVote } from '../hooks/votes/useVoteMutations';
import { VoteStars } from '../components/votes/VoteStars.jsx';
import { CommentBox } from '../components/comments/CommentBox';
import { ItemImage } from '../components/items/ItemImage.jsx';
import { VoteActions } from '../components/votes/VoteActions.jsx';
import { EmptyVoteState } from "../components/votes/EmptyVoteState.jsx";
import { AnimatePresence } from "framer-motion";
import { AnimatedItem } from "../components/animations/AnimatedItem.jsx";
import { BackButton } from "../components/common/BackButton.jsx";

export const VotePage = () => {
  const { categorySlug } = useParams();
  const [showComments, setShowComments] = useState(false);

  const { data, isLoading, isFetching } = useNextCategoryItem(categorySlug);
  const voteMutation = useCreateVote();

  useEffect(() => {
    setShowComments(false);
  }, [data?.data?.id]);

  const item = data?.data;

  const handleVote = (score) => {
    voteMutation.mutate({ item_id: item.id, type: 'vote', score, categorySlug });
  };

  const handleSkip = () => {
    voteMutation.mutate({ item_id: item.id, type: 'skip', categorySlug });
  };

  return (
    <div className="flex flex-col w-full max-w-3xl mx-auto pb-6 lg:pb-0 lg:h-page-content">
      <div className="shrink-0 mb-4 px-2">
        <BackButton />
      </div>

      <div className={`flex flex-col flex-1 min-h-0 transition-opacity duration-300 ${(isFetching && !isLoading) ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>

        <div className="w-full h-full bg-surface rounded-3xl p-4 md:p-6 shadow-xl ring-1 ring-slate-200 flex flex-col relative overflow-hidden">

          {isLoading ? (
            <div className="absolute inset-0 flex flex-col items-center justify-center bg-surface rounded-3xl z-10">
              <Loader2 className="animate-spin text-blue-500 mb-4" size={48} />
              <p className="text-text-normal font-medium animate-pulse">Buscando el siguiente candidato...</p>
            </div>
          ) : !isLoading && !item ? <EmptyVoteState category={categorySlug} /> :
            (
              <>
                <div className="flex-1 min-h-0 mb-6 flex justify-center items-center w-full">
                  <AnimatePresence mode="wait">
                    <AnimatedItem key={item.id} itemKey={item.id} className="w-full h-full flex justify-center items-center">
                      <ItemImage item={item} />
                    </AnimatedItem>
                  </AnimatePresence>
                </div>

                <div className="shrink-0 mb-1 z-10 relative">
                  <VoteStars onVote={handleVote} isPending={voteMutation.isPending} />
                </div>

                <div className="shrink-0">
                  <VoteActions
                    onSkip={handleSkip}
                    showComments={showComments}
                    onToggleComments={() => setShowComments(!showComments)}
                  />
                </div>

                {showComments && (
                  <div className="mt-4 pt-4 border-t border-slate-100 shrink-0 flex flex-col max-h-[35vh]">
                    <CommentBox itemId={item.id} className="h-full" />
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