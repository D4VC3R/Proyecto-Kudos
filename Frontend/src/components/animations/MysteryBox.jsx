import React from 'react';
import { motion } from 'framer-motion';
import { Gift, Sparkles } from 'lucide-react';
import { gamifiedEntryVariants, floatingLoopVariants, pulseLoopVariants } from './../../lib/animations';

export const MysteryBox = ({ onReveal }) => {
  return (
    <motion.div
      variants={gamifiedEntryVariants}
      initial="hidden"
      animate="visible"
      exit="exit"
      className="flex flex-col items-center cursor-pointer group"
      onClick={onReveal}
    >
      <motion.div
        variants={floatingLoopVariants}
        animate="animate"
        className="relative bg-gradient-to-br from-indigo-500 to-purple-600 p-6 sm:p-8 rounded-full shadow-lg shadow-purple-500/30 mb-4 group-hover:scale-105 transition-transform"
      >
        <Gift size={56} className="text-text-btn" strokeWidth={1.5} />
        <motion.div
          variants={pulseLoopVariants}
          animate="animate"
          className="absolute -top-1 -right-1 text-amber-300"
        >
          <Sparkles size={24} />
        </motion.div>
      </motion.div>

      <h2 className="text-xl sm:text-2xl font-bold text-slate-800">
        ¡Tu bonus diario está listo!
      </h2>
      <p className="text-sm sm:text-base text-text-normal mt-2 animate-pulse">
        Toca para abrir
      </p>
    </motion.div>
  );
};