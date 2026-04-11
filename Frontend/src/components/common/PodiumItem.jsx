import React from 'react';
import { motion } from 'framer-motion';
import { Trophy, Medal, UserCircle } from 'lucide-react';
import clsx from 'clsx';

export const PodiumItem = ({ data, rank, type = "item" }) => {
  const isFirst = rank === 1;
  const isSecond = rank === 2;
  const isThird = rank === 3;

  const getRankStyles = () => {
    if (isFirst) return 'bg-gradient-to-r from-yellow-50 to-white border-yellow-400 shadow-[0_0_25px_rgba(250,204,21,0.8)] scale-[1.03] z-30 border-2 py-3 px-4';
    if (isSecond) return 'bg-gradient-to-r from-slate-50 to-white border-slate-400 shadow-[0_0_20px_rgba(148,163,184,0.7)] z-20 border-2 py-2 px-3';
    if (isThird) return 'bg-gradient-to-r from-orange-50 to-white border-orange-400 shadow-[0_0_20px_rgba(217,119,6,0.7)] z-10 border-2 py-2 px-3';
    return 'bg-white border-slate-300 hover:border-blue-400 border py-1.5 px-3 shadow-md transition-colors';
  };

  const getRankIcon = () => {
    if (isFirst) return <Trophy size={28} className="text-yellow-500 drop-shadow-sm" />;
    if (isSecond) return <Medal size={24} className="text-slate-400 drop-shadow-sm" />;
    if (isThird) return <Medal size={24} className="text-orange-400 drop-shadow-sm" />;
    return <span className="text-sm font-bold text-slate-500 w-6 text-center">{rank}</span>;
  };

  const scoreValue = type === "user" ? data.total_kudos : (data.score || data.vote_avg || 0);
  
  let scoreBgClass = "bg-blue-600";
  if (type === "item") {
    scoreBgClass = "bg-red-600";
    if (scoreValue > 6) scoreBgClass = "bg-blue-600";
    else if (scoreValue > 4) scoreBgClass = "bg-orange-500";
  }

  return (
    <motion.div
      variants={{
        hidden: { opacity: 0, y: 10 },
        visible: { opacity: 1, y: 0 },
      }}
      className={clsx(
        'flex items-center gap-3 rounded-2xl transition-all',
        getRankStyles()
      )}
    >
      <div className={clsx("flex shrink-0 items-center justify-center rounded-full bg-white shadow-sm", isFirst ? "h-12 w-12" : isSecond || isThird ? "h-10 w-10" : "h-8 w-8")}>
        {getRankIcon()}
      </div>

      {type === "user" && (
        <div className="shrink-0 flex items-center justify-center w-8 h-8 md:w-10 md:h-10 rounded-full bg-slate-200 text-slate-500 overflow-hidden border border-slate-300">
          {data.avatar ? (
            <img src={data.avatar} alt={data.name} className="w-full h-full object-cover" />
          ) : (
            <UserCircle size={24} />
          )}
        </div>
      )}

      <div className="flex-1 truncate">
        <h4 className={clsx("truncate font-bold text-slate-900", isFirst ? "text-xl" : isSecond || isThird ? "text-base" : "text-sm")}>
          {data.name}
        </h4>
      </div>

      <div className={clsx("flex shrink-0 items-center gap-1 rounded-full font-bold text-white shadow-md", scoreBgClass, isFirst ? "px-3 py-1.5 text-base" : "px-2.5 py-1 text-xs")}>
        {scoreValue} {type === "user" && "K"}
      </div>
    </motion.div>
  );
};
