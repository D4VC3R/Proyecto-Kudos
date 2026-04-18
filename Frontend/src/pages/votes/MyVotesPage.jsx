import React from 'react';
import { motion } from 'framer-motion';
import { Loader2 } from 'lucide-react';
import { useUrlTableState } from '../../hooks/useUrlTableState';
import { useMyVotes } from '../../hooks/votes/useVoteQueries';
import { useDeleteVote, useUpdateVote } from '../../hooks/votes/useVoteMutations';
import { MyVotesHeader } from '../../components/votes/MyVotesHeader';
import { MyVotesEmpty } from '../../components/votes/MyVotesEmpty';
import { MyVoteItemCard } from '../../components/votes/MyVoteItemCard';
import { useSearchParams } from 'react-router-dom';

export const MyVotesPage = () => {
  const { apiFilters, page, goToPage, updateParams } = useUrlTableState();
  const [searchParams] = useSearchParams();
  
  // Aadir type actual
  const currentView = searchParams.get('type') || 'all';
  const finalFilters = { ...apiFilters, type: currentView === 'all' ? undefined : currentView };

  const { data: response, isLoading } = useMyVotes(finalFilters);
  const { mutate: deleteVote, isPending: isDeleting } = useDeleteVote();
  const { mutate: updateVote, isPending: isUpdating } = useUpdateVote();

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-20 min-h-[50vh]">
        <Loader2 className="animate-spin text-blue-500" size={48} />
      </div>
    );
  }

  const votes = response?.data || [];
  const meta = response?.meta || {};

  return (
    <div className="flex w-full flex-col">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4 }}
        className="flex flex-col gap-6"
      >
        <MyVotesHeader meta={meta} currentView={currentView} updateParams={updateParams} />

        {votes.length === 0 ? (
          <MyVotesEmpty />
        ) : (
          <>
            <div className="grid gap-4">
              {votes.map((vote) => (
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

            {/* Paginación */}
            {meta.last_page > 1 && (
              <div className="mt-4 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-slate-200 pt-4">
                <span className="text-sm font-medium text-slate-500">
                  Mostrando pgina {meta.current_page} de {meta.last_page} ({meta.total} votos)
                </span>

                <div className="flex items-center gap-2">
                  <button
                    onClick={() => goToPage(page - 1)}
                    disabled={page === 1}
                    className="flex h-10 items-center justify-center rounded-xl bg-white px-4 text-sm font-bold text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-50 transition-colors"
                  >
                    Anterior
                  </button>
                  <button
                    onClick={() => goToPage(page + 1)}
                    disabled={page >= meta.last_page}
                    className="flex h-10 items-center justify-center rounded-xl bg-white px-4 text-sm font-bold text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-50 transition-colors"
                  >
                    Siguiente
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </motion.div>
    </div>
  );
};

export default MyVotesPage;
