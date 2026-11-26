import React from 'react';
import clsx from 'clsx';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import { UserCircle, ChevronRight } from 'lucide-react';
import {RANK_CONFIG} from '../../../lib/constants.js';
import { getScoreColor } from '../../../lib/formatters.js';
import { staggerItemVariants } from '../../../lib/animations';

import StaggerItem from '../../animations/StaggerItem.jsx';
import StorageImage from '../StorageImage.jsx';

const MotionLink = motion.create(Link);

const PodiumItem = ({ data, rank, type = "item", to }) => {
  const config = RANK_CONFIG[rank] || RANK_CONFIG.default;
  const scoreValue = type === "user" ? data.total_kudos : (data.score || data.vote_avg || 0);
  const scoreBgClass = getScoreColor(type, scoreValue);
  const RankIcon = config.Icon;

  const rowClass = clsx(
    'flex items-center gap-3 rounded-2xl transition-all group',
    to && 'cursor-pointer hover:scale-[1.01] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary',
    config.wrapperClass
  );

  const content = (
    <>
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

      {to && (
        <div className="flex shrink-0 items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
          <ChevronRight size={20} />
        </div>
      )}
    </>
  );

  if (!to) {
    return <StaggerItem className={rowClass}>{content}</StaggerItem>;
  }

  // El enlace es el propio elemento animado (así conserva el z-index del podio como elemento flex);
  // equivale a un StaggerItem sin `alternate` y con índice 0.
  return (
    <MotionLink to={to} variants={staggerItemVariants} custom={0} className={rowClass}>
      {content}
    </MotionLink>
  );
};

export default PodiumItem;