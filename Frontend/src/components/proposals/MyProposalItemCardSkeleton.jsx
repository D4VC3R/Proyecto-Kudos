import React from 'react';
import Skeleton from '../ui/Skeleton.jsx';

const MyProposalItemCardSkeleton = () => {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-surface p-5 shadow-sm ring-1 ring-slate-200">
      <div className="flex items-start gap-4 w-full">

        <Skeleton className="mt-1 h-12 w-12 shrink-0 rounded-xl" />

        <div className="flex flex-col flex-1 gap-2">
          <div className="flex items-center gap-3">
            <Skeleton className="h-6 w-32 sm:w-48" />
            <Skeleton className="h-5 w-24 rounded-full" />
          </div>
          <Skeleton className="h-4 w-24 sm:w-32 mt-0.5" />
        </div>
      </div>

      <div className="flex items-center gap-2 self-end sm:self-center">
        <Skeleton className="h-10 w-10 rounded-xl" />
        <Skeleton className="h-10 w-10 rounded-xl" />
      </div>
    </div>
  );
};

export default MyProposalItemCardSkeleton;