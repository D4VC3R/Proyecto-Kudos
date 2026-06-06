import React from 'react';
import Skeleton from '../ui/skeletons/Skeleton.jsx';

const CategoryCardSkeleton = () => (
  <div className="h-80 w-full rounded-3xl bg-surface shadow-lg ring-1 ring-slate-200 p-6 flex flex-col justify-end relative overflow-hidden">
    <div className="absolute inset-0 bg-slate-100 animate-pulse" />
    <div className="relative z-10">
      <Skeleton className="h-8 w-3/4 mb-3" />
      <Skeleton className="h-4 w-full mb-1" />
      <Skeleton className="h-4 w-2/3" />
    </div>
  </div>
);

export default CategoryCardSkeleton;