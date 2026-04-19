import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { useNextCategoryItem } from '../hooks/categories/useCategoryQueries';
import { useCreateVote } from '../hooks/votes/useVoteMutations';
import { VoteStars } from '../components/vote/VoteStars';
import { CommentBox } from '../components/comments/CommentBox';
import { ItemDetail } from '../components/items/ItemDetail';
import { VoteActions } from '../components/vote/VoteActions';
import {EmptyVoteState} from "../components/vote/EmptyVoteState.jsx";

export const VotePage = () => {
  const { categorySlug } = useParams();
  const [showComments, setShowComments] = useState(false);

  const { data, isLoading, isFetching } = useNextCategoryItem(categorySlug);
  const voteMutation = useCreateVote();


  useEffect(() => {
    setShowComments(false);
  }, [data?.data?.id]);

  if (isLoading) {
    return (
      <div className="flex h-[70vh] items-center justify-center">
        <Loader2 className="animate-spin text-blue-500" size={48} />
      </div>
    );
  }

  const item = data?.data;
  const remaining = data?.meta?.remaining || 0;


  const handleVote = (score) => {
    voteMutation.mutate({ item_id: item.id, type: 'vote', score, categorySlug });
  };

  const handleSkip = () => {
    voteMutation.mutate({ item_id: item.id, type: 'skip', categorySlug });
  };

  return (
    <> {item
      ?
      <div className={`mx-auto flex w-full max-w-3xl flex-col items-center py-8 min-h-screen transition-opacity duration-300 ${isFetching ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>
        <div className="w-full flex items-center justify-between mb-6 px-4 shrink-0">
          <h1 className="text-2xl font-black text-slate-900">Votación en Curso</h1>
          <div className="bg-blue-100 text-blue-800 px-3 py-1.5 rounded-full text-sm font-bold shadow-sm">
            {remaining} {remaining === 1 ? 'restante' : 'restantes'}
          </div>
        </div>

        <div className="w-full h-full bg-white rounded-3xl p-6 md:p-8 shadow-xl ring-1 ring-slate-200 flex flex-col justify-between">
          <div className="shrink-0 mb-6">
            <ItemDetail item={item} />
          </div>

          <div className="shrink-0 mb-8">
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
        </div>
      </div>
      :
      <EmptyVoteState category={categorySlug} />}
    </>

);
};

export default VotePage;
