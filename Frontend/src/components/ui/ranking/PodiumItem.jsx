import React from 'react';
import clsx from 'clsx';
import { UserCircle, ChevronRight } from 'lucide-react';
import {RANK_CONFIG} from '../../../lib/constants.js';
import { getScoreColor } from '../../../lib/formatters.js';

import StaggerItem from '../../animations/StaggerItem.jsx';
import StorageImage from '../StorageImage.jsx';


const PodiumItem = ({ data, rank, type = "item", onClick }) => {
  const config = RANK_CONFIG[rank] || RANK_CONFIG.default;
  const scoreValue = type === "user" ? data.total_kudos : (data.score || data.vote_avg || 0);
  const scoreBgClass = getScoreColor(type, scoreValue);
  const RankIcon = config.Icon;

  return (
    <StaggerItem
      className={clsx(
        'flex items-center gap-3 rounded-2xl transition-all group',
        onClick && 'cursor-pointer hover:scale-[1.01]',
        config.wrapperClass
      )}
      onClick={onClick}
    >
      <div className={clsx("flex shrink-0 items-center justify-center rounded-full bg-surface shadow-sm", config.iconWrapperClass)}>
        {RankIcon ? (
          <RankIcon size={config.iconSize} className={config.iconClass} />
        ) : (
          <span className="text-sm font-bold text-text-normal w-6 text-center">{rank}</span>
        )}
      </div>

      {type === "user" && (
        <div className="shrink-0 flex items-center justify-center w-8 h-8 md:w-10 md:h-10 rounded-full bg-slate-200 text-text-normal overflow-hidden border border-slate-300">
          <StorageImage
            src={data.avatar}
            alt={data.name || 'Avatar'}
            className="w-full h-full"
            fallbackIcon={UserCircle}
          />
        </div>
      )}

      <div className="flex-1 truncate">
        <h4 className={clsx("truncate font-bold text-text-highlight", config.textClass)}>
          {data.name}
        </h4>
      </div>

      <div className={clsx("flex shrink-0 items-center gap-1 rounded-full font-bold text-text-btn shadow-md", scoreBgClass, config.scoreClass)}>
        {scoreValue} {type === "user" && "K"}
      </div>

      {onClick && (
        <div className="flex shrink-0 items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
          <ChevronRight size={20} />
        </div>
      )}
    </StaggerItem>
  );
};

export default PodiumItem;