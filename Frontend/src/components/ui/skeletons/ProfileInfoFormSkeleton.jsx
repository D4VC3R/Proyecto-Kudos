import React from 'react';
import Skeleton from './Skeleton.jsx';

const ProfileInfoFormSkeleton = () => {
  return (
    <div className="flex w-full flex-col gap-6 lg:flex-row lg:items-start">
      <div className="flex flex-col gap-4 rounded-md border border-line bg-surface p-6 lg:w-96 lg:shrink-0">
        <div className="flex items-center gap-4">
          <Skeleton className="h-20 w-20 shrink-0 rounded-full" />
          <div className="flex flex-1 flex-col gap-2">
            <Skeleton className="h-7 w-3/4" />
            <Skeleton className="h-4 w-1/2" />
          </div>
        </div>
        <Skeleton className="h-4 w-full" />
        <div className="flex flex-wrap gap-2">
          <Skeleton className="h-7 w-20 rounded-full" />
          <Skeleton className="h-7 w-16 rounded-full" />
        </div>
      </div>

      <div className="w-full flex-1 rounded-md border border-line bg-surface p-6 md:p-8">
        <Skeleton className="mb-6 h-7 w-1/3" />

        <div className="flex flex-col gap-5">
          <div>
            <Skeleton className="mb-2 h-4 w-32" />
            <Skeleton className="h-10 w-full rounded-sm" />
          </div>

          <div>
            <Skeleton className="mb-2 h-4 w-24" />
            <Skeleton className="h-32 w-full rounded-sm" />
          </div>

          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <Skeleton className="mb-2 h-4 w-16" />
              <Skeleton className="h-10 w-full rounded-sm" />
            </div>
            <div>
              <Skeleton className="mb-2 h-4 w-32" />
              <Skeleton className="h-10 w-full rounded-sm" />
            </div>
          </div>

          <div className="mt-4 flex justify-end border-t border-line pt-4">
            <Skeleton className="h-11 w-40 rounded-sm" />
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProfileInfoFormSkeleton;
