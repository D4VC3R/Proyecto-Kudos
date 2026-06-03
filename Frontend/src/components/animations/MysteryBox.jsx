import React from 'react';
import { motion } from 'framer-motion';
import { Sparkles } from 'lucide-react';
import LogoKudos from "../layout/LogoKudos.jsx";
import { gamifiedEntryVariants, floatingLoopVariants, pulseLoopVariants } from './../../lib/animations';

const MysteryBox = ({ onReveal }) => {
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
        className="relative bg-gradient-to-br from-blue-500 to-blue-600 p-6 sm:p-8 rounded-full shadow-lg shadow-purple-500/30 mb-4 group-hover:scale-105 transition-transform"
      >
        <LogoKudos  className="w-14 h-14 lg:h-16 lg:w-16" />
        <motion.div
          variants={pulseLoopVariants}
          animate="animate"
          className="absolute -top-1 -right-1 text-amber-300"
        >
          <Sparkles size={24} />
        </motion.div>
      </motion.div>

      <h2 className="text-xl sm:text-2xl font-bold text-slate-800">
        ¡Recoge tu <span className="text-primary">bonus</span> diario!
      </h2>
      <p className="text-sm sm:text-base text-accent mt-2 animate-pulse">
        Toca para abrir
      </p>
    </motion.div>
  );
};

export default MysteryBox;