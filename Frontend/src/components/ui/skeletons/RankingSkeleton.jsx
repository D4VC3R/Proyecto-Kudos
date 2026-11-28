import React from "react";
import Skeleton from "./Skeleton";

const RankingSkeleton = () => {
  const rows = Array.from({ length: 10 });

  return (
    <div aria-hidden="true" className="grid grid-cols-1 gap-6 rounded-md border border-line px-5 pb-7 pt-8 md:grid-cols-band md:gap-10 md:px-10 md:pb-8 md:pt-10">
      <div className="flex flex-col gap-3">
        <Skeleton className="h-4 w-24" /><Skeleton className="h-10 w-3/4" /><Skeleton className="h-4 w-full max-w-md" />
      </div>
      <div className="flex flex-col gap-3">{rows.map((_, i) => <Skeleton key={i} className="h-11 w-full" />)}</div>
    </div>
  );
};
export default RankingSkeleton;
