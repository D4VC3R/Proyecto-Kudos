import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Trophy, Sparkles, Zap } from 'lucide-react';
import {rewardRevealVariants, popInVariants, fadeUpVariants, springPopVariants} from './../../lib/animations';

export const RewardReveal = ({ data, displayedKudos, isMultiplierActive }) => {
  const isUpgraded = data.isCritical && isMultiplierActive;
  const iconBg = isUpgraded ? 'bg-purple-100 text-purple-600 shadow-purple-200' : 'bg-amber-100 text-amber-500 shadow-amber-200';
  const textColor = isUpgraded ? 'text-purple-600' : 'text-primary';

  return (
    <motion.div
      variants={rewardRevealVariants}
      initial="hidden"
      animate="visible"
      className="flex flex-col items-center w-full"
    >
      <motion.div
        layout
        className={`flex items-center justify-center h-20 w-20 sm:h-24 sm:w-24 rounded-full mb-4 shadow-inner transition-colors duration-700 ${iconBg}`}
      >
        <Trophy size={48} strokeWidth={2} />
      </motion.div>

      <motion.h2 variants={popInVariants} className="text-3xl sm:text-5xl font-extrabold text-slate-800 tracking-tight">
        ¡Día {data.streak}!
      </motion.h2>

      <motion.div variants={fadeUpVariants} className="mt-3 flex flex-col items-center">
        <p className="text-base sm:text-lg text-nav-item">Has ganado</p>
        <span className={`font-black text-4xl sm:text-5xl transition-colors duration-700 ${textColor}`}>
          +{displayedKudos}
        </span>
        <p className="text-sm text-text-normal font-semibold mt-1">Kudos</p>
      </motion.div>

      <AnimatePresence>
        {isUpgraded && (
          <motion.div
            variants={springPopVariants}
            initial="hidden"
            animate="visible"
            exit="exit"
            className="flex items-center gap-2 text-purple-700 font-black mt-4 bg-purple-100 px-4 py-2 rounded-full border border-purple-300 shadow-md"
          >
            <Zap size={18} className="animate-pulse text-purple-600" fill="currentColor" />
            <span className="text-sm uppercase tracking-wider">¡Multiplicador x{data.multiplier}!</span>
          </motion.div>
        )}
      </AnimatePresence>

      {data.isNewRecord && (
        <motion.div variants={fadeUpVariants} className="flex items-center gap-2 bg-gradient-to-r from-amber-100 to-amber-50 text-amber-800 px-4 py-2 sm:px-6 sm:py-3 rounded-full font-bold mt-5 shadow-sm border border-amber-200">
          <Sparkles size={18} className="text-amber-500 animate-pulse" />
          <span className="text-sm sm:text-base">¡Nuevo Récord Histórico!</span>
        </motion.div>
      )}
    </motion.div>
  );
};