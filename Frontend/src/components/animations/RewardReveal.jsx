import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Trophy, Sparkles, Zap, Star } from 'lucide-react';
import {rewardRevealVariants, popInVariants, fadeUpVariants, springPopVariants} from './../../lib/animations';

// El dorado de los kudos solo se usa como fondo (con `on-kudos`) o como borde, nunca como color de texto.
const RewardReveal = ({ data, displayedKudos, isMultiplierActive }) => {
  const isUpgraded = data.isCritical && isMultiplierActive;
  const trophyStyles = isUpgraded ? 'bg-kudos text-on-kudos shadow-raised' : 'bg-kudos/25 text-ink';
  const amountStyles = isUpgraded ? 'bg-kudos text-on-kudos' : 'bg-kudos/25 text-ink';

  return (
    <motion.div
      variants={rewardRevealVariants}
      initial="hidden"
      animate="visible"
      className="flex flex-col items-center w-full"
    >
      <motion.div
        layout
        className={`flex items-center justify-center h-20 w-20 sm:h-24 sm:w-24 rounded-full mb-4 transition-colors duration-700 ${trophyStyles}`}
      >
        <Trophy size={48} strokeWidth={2} aria-hidden="true" />
      </motion.div>

      <motion.h2 variants={popInVariants} className="font-display text-h1 text-ink">
        ¡Día <span className="text-accent">{data.streak}</span>!
      </motion.h2>

      <motion.div variants={fadeUpVariants} className="mt-3 flex flex-col items-center">
        <p className="text-body text-ink-muted">Has ganado</p>
        <span className={`mt-2 inline-flex items-center gap-2 rounded-full px-5 py-2 font-display text-h1 tabular-nums transition-colors duration-700 ${amountStyles}`}>
          <Star size={28} fill="currentColor" aria-hidden="true" />
          +{displayedKudos}
        </span>
        <p className="mt-1 text-h3 text-ink">Kudos</p>
      </motion.div>

      <AnimatePresence>
        {isUpgraded && (
          <motion.div
            variants={springPopVariants}
            initial="hidden"
            animate="visible"
            exit="exit"
            className="mt-4 flex items-center gap-2 rounded-full bg-kudos px-4 py-2 font-semibold text-on-kudos shadow-raised"
          >
            <Zap size={18} className="animate-pulse" fill="currentColor" aria-hidden="true" />
            <span className="text-small uppercase">¡Multiplicador x{data.multiplier}!</span>
          </motion.div>
        )}
      </AnimatePresence>

      {data.isNewRecord && (
        <motion.div variants={fadeUpVariants} className="mt-5 flex items-center gap-2 rounded-full border border-kudos bg-surface px-4 py-2 font-semibold text-ink">
          <Sparkles size={18} className="animate-pulse" aria-hidden="true" />
          <span className="text-small">¡Nuevo récord histórico!</span>
        </motion.div>
      )}
    </motion.div>
  );
};

export default RewardReveal;
