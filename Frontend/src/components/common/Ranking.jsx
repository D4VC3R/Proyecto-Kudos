import React from 'react';
import { motion } from 'framer-motion';
import { Trophy } from 'lucide-react';
import { staggereContainerVariants } from '../../lib/animations';
import { PodiumItem } from './PodiumItem';

const Ranking = ({ title, items = [], page = 1, itemsPerPage = 10, type = "item" }) => {
  return (
    <div className="flex aspect-square w-full flex-col rounded-3xl bg-white p-6 md:p-8 shadow-2xl ring-2 ring-slate-200 overflow-hidden">
      <div className="mb-6 flex items-center justify-between shrink-0 border-b border-slate-100 pb-4">
        <h2 className="text-2xl font-black text-slate-900">{title}</h2>
        <Trophy className="text-yellow-400" size={32} />
      </div>

      <motion.div
        variants={staggereContainerVariants}
        initial="hidden"
        animate="visible"
        className="flex flex-col gap-1.5 overflow-y-auto pr-2 pb-2 scrollbar-thin scrollbar-thumb-slate-200"
      >
        {items.map((item, index) => (
          <PodiumItem key={item.id} data={item} rank={(page - 1) * itemsPerPage + index + 1} type={type} />
        ))}
      </motion.div>
    </div>
  );
};

export default Ranking;