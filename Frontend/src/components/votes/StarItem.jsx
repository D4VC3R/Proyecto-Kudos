import clsx from "clsx";
import { Star } from 'lucide-react';
import {STAR_SIZE_CLASSES} from "../../lib/constants.js";

const StarItem = ({fillPercent}) => {
  return (
    <div className="relative group">
      <Star
        className={clsx(STAR_SIZE_CLASSES, "text-accent drop-shadow-sm transition-transform group-hover:scale-110")}
        strokeWidth={1.5}
        fill="white"
      />
      <div
        className="absolute top-0 left-0 overflow-hidden pointer-events-none h-full"
        style={{width: `${fillPercent}%`}}
      >
        <Star
          className={clsx(STAR_SIZE_CLASSES, "text-accent fill-accent drop-shadow-[0_0_8px_rgba(250,204,21,0.6)] transition-transform group-hover:scale-110")}
          strokeWidth={1.5}
        />
      </div>
    </div>
  );
}

export default StarItem;