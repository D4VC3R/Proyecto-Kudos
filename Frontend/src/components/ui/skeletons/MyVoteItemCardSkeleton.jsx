import React from 'react';
import Skeleton from './Skeleton.jsx';

const MyVoteItemCardSkeleton = () => {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-surface p-5 shadow-sm ring-1 ring-slate-200">
      <div className="flex items-start gap-4 flex-1 w-full">
        <Skeleton className="mt-1 h-16 w-14 shrink-0 rounded-xl" />

        <div className="flex flex-col w-full gap-2">
          <div className="flex items-center gap-3 flex-wrap">
            <Skeleton className="h-6 w-40 sm:w-56" />
            <Skeleton className="h-5 w-16 rounded-full" />
          </div>

          <Skeleton className="h-4 w-48 mt-1" />
          <Skeleton className="mt-3 h-9 w-32 rounded-xl" />
        </div>
      </div>

      <div className="flex items-center gap-2 self-end sm:self-center">
        <Skeleton className="h-10 w-10 rounded-xl" />
      </div>
    </div>
  );
};

export default MyVoteItemCardSkeleton;