import React, { useMemo } from 'react';
import { Loader2 } from 'lucide-react';
import { useInfiniteMyVotes } from '../hooks/votes/useVoteQueries.js';
import { useDeleteVote, useUpdateVote } from '../hooks/votes/useVoteMutations.js';
import { MyVotesHeader } from '../components/votes/MyVotesHeader.jsx';
import { MyVotesEmpty } from '../components/votes/MyVotesEmpty.jsx';
import { MyVoteItemCard } from '../components/votes/MyVoteItemCard.jsx';
import { MyVoteItemCardSkeleton } from '../components/votes/MyVoteItemCardSkeleton.jsx';
import { useInfiniteScroll } from '../hooks/common/useInfiniteScroll.js';
import { FadeUp } from "../components/animations/FadeUp.jsx";
import { useFilters } from '../hooks/common/useFilters.js';

export const MyVotesPage = () => {

  const { filters, setFilters } = useFilters({
    initialFilters: { type: 'all', category_slug: '' }
  });

  const currentView = filters.type || 'all';
  const currentCategory = filters.category_slug;

  const finalFilters = {
    type: currentView === 'all' ? undefined : currentView,
    category_slug: currentCategory || undefined
  };

  const updateParams = (updates) => {
    setFilters((prev) => ({ ...prev, ...updates }));
  };

  const {data: response, isLoading, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage} = useInfiniteMyVotes(finalFilters, 15);

  const { mutate: deleteVote, isPending: isDeleting } = useDeleteVote();
  const { mutate: updateVote, isPending: isUpdating } = useUpdateVote();

  const lastElementRef = useInfiniteScroll({
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  });

  // Memorizamos el aplanamiento de datos para no saturar la RAM
  const allVotes = useMemo(() => {
    return response?.pages.flatMap(page => page.data) || [];
  }, [response]);

  const meta = useMemo(() => {
    return response?.pages[0]?.meta || {};
  }, [response]);

  const showSkeletons = isLoading || (isFetching && allVotes.length === 0);
  const isBackgroundUpdating = isFetching && !isFetchingNextPage && !showSkeletons;

  return (
      <div className="flex w-full flex-col relative">
        <FadeUp className={`flex flex-col gap-6 relative transition-opacity duration-200 ${isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}>

          <MyVotesHeader
              meta={meta}
              currentView={currentView}
              currentCategory={currentCategory}
              updateParams={updateParams}
          />

          {showSkeletons ? (
              <div className="grid gap-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <MyVoteItemCardSkeleton key={i} />
                ))}
              </div>
          ) : allVotes.length === 0 ? (
              <MyVotesEmpty />
          ) : (
              <>
                <div className="grid gap-4">
                  {allVotes.map((vote) => (
                      <MyVoteItemCard
                          key={vote.id}
                          vote={vote}
                          isDeleting={isDeleting}
                          isUpdating={isUpdating}
                          onDelete={deleteVote}
                          onUpdate={updateVote}
                      />
                  ))}
                </div>

                <div ref={lastElementRef} className="flex h-12 w-full items-center justify-center py-4">
                  {isFetchingNextPage && <Loader2 className="animate-spin text-blue-500" size={24} />}
                </div>

                {!hasNextPage && allVotes.length > 0 && (
                    <div className="text-center py-4 text-slate-400 font-medium text-sm">
                      Has llegado al final de tu historial.
                    </div>
                )}
              </>
          )}
        </FadeUp>
      </div>
  );
};