import React from 'react';
import Skeleton from './Skeleton.jsx';

const ProfileInfoFormSkeleton = () => {
  return (
    <div className="flex flex-col gap-8 md:flex-row max-w-4xl mx-auto w-full">
      <div className="flex flex-col items-center p-6 bg-background rounded-3xl border border-border md:w-1/3 h-fit">
        <Skeleton className="w-32 h-32 rounded-full mb-4 ring-4 ring-white shadow-lg" />
        <Skeleton className="h-8 w-3/4 mb-2" />
        <Skeleton className="h-4 w-full mb-6" />
        <div className="flex flex-wrap justify-center gap-2 w-full">
          <Skeleton className="h-6 w-20 rounded-full" />
          <Skeleton className="h-6 w-16 rounded-full" />
        </div>
      </div>

      <div className="flex-1 bg-surface rounded-3xl p-6 md:p-8 shadow-sm border border-border">
        <Skeleton className="h-7 w-1/3 mb-6 border-b border-slate-100 pb-4" />

        <div className="flex flex-col gap-5">
          <div>
            <Skeleton className="h-4 w-32 mb-2" />
            <Skeleton className="h-11 w-full rounded-xl" />
          </div>

          <div>
            <Skeleton className="h-4 w-24 mb-2" />
            <Skeleton className="h-32 w-full rounded-xl" />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <Skeleton className="h-4 w-16 mb-2" />
              <Skeleton className="h-11 w-full rounded-xl" />
            </div>
            <div>
              <Skeleton className="h-4 w-32 mb-2" />
              <Skeleton className="h-11 w-full rounded-xl" />
            </div>
          </div>

          <div className="flex justify-end mt-4 pt-4 border-t border-slate-100">
            <Skeleton className="h-11 w-40 rounded-xl" />
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProfileInfoFormSkeleton;