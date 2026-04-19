import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Loader2, ArrowUp } from 'lucide-react';
import { useInfiniteMyVotes } from '../../hooks/votes/useVoteQueries';
import { useDeleteVote, useUpdateVote } from '../../hooks/votes/useVoteMutations';
import { MyVotesHeader } from '../../components/votes/MyVotesHeader';
import { MyVotesEmpty } from '../../components/votes/MyVotesEmpty';
import { MyVoteItemCard } from '../../components/votes/MyVoteItemCard';
import { useInfiniteScroll } from '../../hooks/useInfiniteScroll';
import { mergeFilters } from '../../lib/filters';

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

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-20 min-h-[50vh]">
        <Loader2 className="animate-spin text-blue-500" size={48} />
      </div>
    );
  }
  const pages = response?.pages || [];
  const allVotes = pages.flatMap(page => page.data);
  const meta = pages[0]?.meta || {}; // Uses meta of first page for total count
  
  const showScrollTop = pages.length > 1;

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const isBackgroundUpdating = isFetching && !isFetchingNextPage;

  return (
    <div className="flex w-full flex-col relative">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4 }}
        className={`flex flex-col gap-6 relative transition-opacity duration-200 ${isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}
      >
        <MyVotesHeader meta={meta} currentView={currentView} currentCategory={currentCategory} updateParams={updateParams} />

        {allVotes.length === 0 && !isBackgroundUpdating ? (
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
      </motion.div>
      <AnimatePresence>
        {showScrollTop && (
          <motion.button
            initial={{ opacity: 0, scale: 0.8 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.8 }}
            onClick={scrollToTop}
            className="fixed bottom-8 right-8 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-white shadow-lg hover:bg-slate-800 hover:-translate-y-1 transition-all"
            aria-label="Volver arriba"
          >
            <ArrowUp size={24} />
          </motion.button>
        )}
      </AnimatePresence>
    </div>
  );
};
