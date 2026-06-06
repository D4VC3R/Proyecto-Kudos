import React from "react";
import Skeleton from "./Skeleton.jsx";

const SliderSkeleton = () => {
  const simItems = Array.from({ length: 5 });
  
  return (
    <div className="mb-8 flex w-full flex-col justify-center rounded-3xl bg-surface shadow-xl ring-1 ring-border overflow-hidden">
      <div className="mt-6 px-8 border-b border-border pb-4">
        <Skeleton className="h-8 md:h-10 w-64 rounded-lg" />
      </div>
      <div className="flex gap-6 px-6 py-8 overflow-hidden">
        {simItems.map((item, i) => (
          <Skeleton
            key={i}
            className="w-40 sm:w-48 shrink-0 aspect-[3/4] rounded-3xl ring-1 ring-slate-200"
          />
        ))}
      </div>
    </div>
  )
}

export default SliderSkeleton;