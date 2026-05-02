import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { useNextCategoryItem } from '../hooks/categories/useCategoryQueries';
import { useCreateVote } from '../hooks/votes/useVoteMutations';
import { VoteStars } from '../components/votes/VoteStars.jsx';
import { CommentBox } from '../components/comments/CommentBox';
import { ItemDetail } from '../components/items/ItemDetail';
import { VoteActions } from '../components/votes/VoteActions.jsx';
import { EmptyVoteState } from "../components/votes/EmptyVoteState.jsx";
import { AnimatePresence } from "framer-motion";
import { AnimatedItem } from "../components/animations/AnimatedItem.jsx";
import {SectionHeader} from "../components/common/SectionHeader.jsx";

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

  // Manejo del estado vacío cuando no hay carga activa
  if (!isLoading && !item) {
    return <EmptyVoteState category={categorySlug} />;
  }

  return (
    <div className={`mx-auto flex w-full max-w-3xl flex-col items-center py-8 min-h-screen transition-opacity duration-300 ${(isFetching && !isLoading) ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>

      <div className="w-full mb-6">
        <SectionHeader
          title="Votación en"
          highlight="Curso"
        />
      </div>

      <div className="w-full bg-white rounded-3xl p-6 md:p-8 shadow-xl ring-1 ring-slate-200 flex flex-col justify-between relative min-h-[600px] md:min-h-[700px]">

        {isLoading ? (
          <div className="absolute inset-0 flex flex-col items-center justify-center rounded-3xl z-10">
            <Loader2 className="animate-spin text-blue-500 mb-4" size={48} />
            <p className="text-slate-500 font-medium animate-pulse">Buscando el siguiente candidato...</p>
          </div>
        ) : (

          <>
            <div className="shrink-0 mb-6 min-h-[350px] md:min-h-[380px] xl:min-h-[450px] flex justify-center">
              <AnimatePresence mode="wait">
                <AnimatedItem key={item.id} itemKey={item.id}>
                  <ItemDetail item={item} />
                </AnimatedItem>
              </AnimatePresence>
            </div>

            <div className="shrink-0 mb-8 z-10 relative">
              <VoteStars onVote={handleVote} isPending={voteMutation.isPending} />
            </div>

            <VoteActions
              onSkip={handleSkip}
              isPending={voteMutation.isPending}
              showComments={showComments}
              onToggleComments={() => setShowComments(!showComments)}
            />

            {showComments && (
              <div className="mt-2 pt-2 border-t border-slate-100">
                <CommentBox itemId={item.id} />
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default VotePage;