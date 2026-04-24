import React, { useRef, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Trophy, Loader2 } from 'lucide-react';
import { staggereContainerVariants } from '../../lib/animations.js';
import { PodiumItem } from '../common/PodiumItem.jsx';

const Ranking = ({
  title,
  items = [],
  page = 1,
  itemsPerPage = 10,
  type = "item",
  fetchNextPage,
  hasNextPage,
  isFetchingNextPage,
  disableScroll = false
}) => {
  const observerTarget = useRef(null);

  useEffect(() => {
    if (!fetchNextPage) return;

    const observer = new IntersectionObserver(
      entries => {
        if (entries[0].isIntersecting && hasNextPage && !isFetchingNextPage) {
          fetchNextPage();
        }
      },
      { threshold: 0.1 }
    );

    if (observerTarget.current) {
      observer.observe(observerTarget.current);
    }

    return () => {
      if (observerTarget.current) {
        observer.unobserve(observerTarget.current);
      }
    };
  }, [fetchNextPage, hasNextPage, isFetchingNextPage]);

  // Adjust height to be automatic if scroll is disabled, else fixed 600px
  const containerHeightClass = disableScroll ? 'h-auto min-h-[600px]' : 'h-[600px]';
  const overflowClass = disableScroll ? 'overflow-visible' : 'overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200';

  return (
    <div className={`flex ${containerHeightClass} w-full flex-col rounded-3xl bg-white p-6 md:p-8 shadow-2xl ring-2 ring-slate-200 overflow-hidden`}>
      <div className="mb-6 flex items-center justify-between shrink-0 border-b border-slate-100 pb-4">
        <h2 className="text-2xl font-black text-slate-900">{title}</h2>
        <Trophy className="text-yellow-400" size={32} />
      </div>

      <motion.div
        variants={staggereContainerVariants}
        initial="hidden"
        animate="visible"
        className={`flex flex-col gap-1.5 pr-2 pb-2 ${overflowClass}`}
      >
        {items.map((item, index) => {
          const rank = disableScroll ? ((page - 1) * itemsPerPage) + index + 1 : index + 1;
          return (
            <PodiumItem key={`${item.id}-${index}`} data={item} rank={rank} type={type} />
          );
        })}

        {fetchNextPage && (
          <div ref={observerTarget} className="flex h-12 items-center justify-center py-4">
            {isFetchingNextPage && <Loader2 className="animate-spin text-blue-500" size={24} />}
          </div>
        )}
      </motion.div>
    </div>
  );
};

export default Ranking;