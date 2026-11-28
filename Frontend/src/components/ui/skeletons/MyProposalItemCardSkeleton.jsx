import React from 'react';
import Skeleton from './Skeleton.jsx';

const MyProposalItemCardSkeleton = () => {
  return (
    <div className="flex flex-col gap-4 rounded-md border border-line bg-surface p-4 sm:flex-row sm:items-center">
      <div className="flex w-full min-w-0 flex-1 items-start gap-4">
        <Skeleton className="aspect-poster w-12 shrink-0 rounded-sm" />

        <div className="flex flex-1 flex-col gap-2">
          <div className="flex flex-wrap items-center gap-2">
            <Skeleton className="h-6 w-32 sm:w-48" />
            <Skeleton className="h-5 w-24 rounded-full" />
          </div>
          <Skeleton className="h-4 w-24 sm:w-32" />
        </div>
      </div>

      <div className="flex gap-2 sm:shrink-0">
        <Skeleton className="h-11 w-24 rounded-sm" />
        <Skeleton className="h-11 w-28 rounded-sm" />
      </div>
    </div>
  );
};

export default MyProposalItemCardSkeleton;
