import React, { useRef, useEffect } from 'react';
import { Trophy} from 'lucide-react';
import PodiumItem from './PodiumItem.jsx';
import StaggerGrid from '../animations/StaggerGrid.jsx';

const Ranking = ({
  title,
  items = [],
  page = 1,
  itemsPerPage = 10,
  type = "item",
  fetchNextPage,
  hasNextPage,
  isFetchingNextPage,
  disableScroll = false,
  onItemClick
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

  const containerHeightClass = disableScroll ? 'h-auto min-h-[760px]' : 'h-[800px]';
  const overflowClass = disableScroll ? 'overflow-visible' : 'overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200';

  return (
    <div className={`flex ${containerHeightClass} w-full flex-col rounded-3xl bg-surface p-6 md:p-8 shadow-2xl ring-2 ring-slate-200 overflow-hidden`}>
      <div className="mb-6 flex items-center justify-between shrink-0 border-b border-slate-100 pb-4">
        <h2 className="text-2xl font-black text-text-highlight">Top <span className="text-primary">{title}</span></h2>
        <Trophy className="text-accent" size={32} />
      </div>

      <StaggerGrid
        className={`flex flex-col gap-1.5 pr-2 pb-2 ${overflowClass}`}
      >
        {items.map((item, index) => {
          const rank = disableScroll ? ((page - 1) * itemsPerPage) + index + 1 : index + 1;
          return (
            <PodiumItem key={`${item.id}-${index}`} data={item} rank={rank} type={type} onClick={() => onItemClick && onItemClick(item)} />
          );
        })}

        {fetchNextPage && (
          <div ref={observerTarget} className="flex h-12 items-center justify-center py-4">
          </div>
        )}
      </StaggerGrid>
    </div>
  );
};

export default Ranking;