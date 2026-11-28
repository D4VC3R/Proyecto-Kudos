import React from 'react';
import Skeleton from './Skeleton.jsx';

const MyVoteItemCardSkeleton = () => {
  return (
    <div className="flex flex-col gap-4 rounded-md border border-line bg-surface p-4 sm:flex-row sm:items-center">
      <div className="flex w-full min-w-0 flex-1 items-start gap-4">
        <Skeleton className="aspect-poster w-12 shrink-0 rounded-sm" />

        <div className="flex w-full flex-col gap-2">
          <div className="flex flex-wrap items-center gap-2">
            <Skeleton className="h-6 w-40 sm:w-56" />
            <Skeleton className="h-5 w-16 rounded-full" />
          </div>

          <Skeleton className="h-4 w-48" />
        </div>
      </div>

      <Skeleton className="h-11 w-28 rounded-sm sm:shrink-0" />
    </div>
  );
};

export default MyVoteItemCardSkeleton;
