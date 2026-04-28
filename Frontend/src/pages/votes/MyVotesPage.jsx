import React from 'react';
import { AnimatePresence } from 'framer-motion';
import { Loader2, ArrowUp } from 'lucide-react';
import { useInfiniteMyVotes } from '../../hooks/votes/useVoteQueries';
import { useDeleteVote, useUpdateVote } from '../../hooks/votes/useVoteMutations';
import { MyVotesHeader } from '../../components/votes/MyVotesHeader';
import { MyVotesEmpty } from '../../components/votes/MyVotesEmpty';
import { MyVoteItemCard } from '../../components/votes/MyVoteItemCard';
import { MyVoteItemCardSkeleton } from '../../components/votes/MyVoteItemCardSkeleton';
import { useInfiniteScroll } from '../../hooks/useInfiniteScroll';
import { mergeFilters } from '../../lib/filters';
import { FadeUp } from "../../components/animations/FadeUp.jsx";
import { PopButton } from "../../components/animations/PopButton.jsx";

export const MyVotesPage = ({ filters = { type: 'all', category_slug: undefined }, setFilters }) => {
  const currentView = filters.type || 'all';
  const currentCategory = filters.category_slug;

  const finalFilters = {
    type: currentView === 'all' ? undefined : currentView,
    category_slug: currentCategory || undefined
  };

  const updateParams = (updates) => {
    if (setFilters) {
      setFilters((prev) => mergeFilters(prev, updates));
    }
  };

  const {
    data: response,
    isLoading,
    isFetching,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  } = useInfiniteMyVotes(finalFilters, 15);

  const { mutate: deleteVote, isPending: isDeleting } = useDeleteVote();
  const { mutate: updateVote, isPending: isUpdating } = useUpdateVote();

  const lastElementRef = useInfiniteScroll({
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  });

  const pages = response?.pages || [];
  const allVotes = pages.flatMap(page => page.data);
  const meta = pages[0]?.meta || {};

  const showScrollTop = pages.length > 1;

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const showSkeletons = isLoading || (isFetching && allVotes.length === 0);
  const isBackgroundUpdating = isFetching && !isFetchingNextPage && !showSkeletons;

  return (
    <div className="flex w-full flex-col relative">
      <FadeUp className={`flex flex-col gap-6 relative transition-opacity duration-200 ${isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}>

        <MyVotesHeader meta={meta} currentView={currentView} currentCategory={currentCategory} updateParams={updateParams} />

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

      <AnimatePresence>
        {showScrollTop && (
          <PopButton
            onClick={scrollToTop}
            className="fixed bottom-8 right-8 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-white shadow-lg hover:bg-slate-800 hover:-translate-y-1 transition-all"
            aria-label="Volver arriba"
          >
            <ArrowUp size={24} />
          </PopButton>
        )}
      </AnimatePresence>
    </div>
  );
};