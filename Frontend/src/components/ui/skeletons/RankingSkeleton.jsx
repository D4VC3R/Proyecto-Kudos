import React from "react";
import Skeleton from "./Skeleton";

const RankingSkeleton = () => {
  const simRankings = Array.from({ length: 10 });

  return (
    <div className="flex h-[800px] w-full flex-col rounded-3xl bg-surface p-6 md:p-8 shadow-2xl ring-2 ring-slate-200 overflow-hidden">
      <div className="mb-6 flex items-center justify-between shrink-0 border-b border-slate-100 pb-4">
        <Skeleton className="h-8 w-48 rounded-lg" />
        <Skeleton className="h-10 w-10 rounded-full" />
      </div>
      <div className="flex flex-col gap-3">
        {simRankings.map((item, i) => (
          <Skeleton key={i} className="h-20 w-full rounded-2xl" />
        ))}
      </div>
    </div>
  );
}
export default RankingSkeleton;