import React from "react";
import Skeleton from "./Skeleton.jsx";


const HeaderSkeleton = () => {

  return (
    <div className="flex flex-col gap-4 border-b border-border pb-2 md:pb-4">
      <Skeleton className="h-12 w-3/4 max-w-lg md:h-16 rounded-xl"/>
      <Skeleton className="h-6 w-full max-w-2xl rounded-lg mt-2"/>
    </div>
  );

};

export default HeaderSkeleton;