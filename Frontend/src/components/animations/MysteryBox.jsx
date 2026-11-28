import React from 'react';
import { motion } from 'framer-motion';
import { Gift, Sparkles } from 'lucide-react';
import { gamifiedEntryVariants, floatingLoopVariants, pulseLoopVariants } from './../../lib/animations';

const MysteryBox = ({ onReveal }) => {
  return (
    <motion.button
      type="button"
      variants={gamifiedEntryVariants}
      initial="hidden"
      animate="visible"
      exit="exit"
      className="flex flex-col items-center rounded-lg p-2 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
      onClick={onReveal}
    >
      <motion.div
        variants={floatingLoopVariants}
        animate="animate"
        className="relative mb-4 rounded-full bg-kudos p-6 text-on-kudos shadow-raised transition-transform group-hover:scale-105 sm:p-8"
      >
        <Gift size={56} aria-hidden="true" />
        <motion.div
          variants={pulseLoopVariants}
          animate="animate"
          className="absolute -top-1 -right-1 text-ink"
        >
          <Sparkles size={24} aria-hidden="true" />
        </motion.div>
      </motion.div>

      <span className="font-display text-h2 text-ink">
        ¡Recoge tu <span className="text-accent">bonus</span> diario!
      </span>
      <span className="mt-2 text-small text-ink-muted animate-pulse">
        Toca para abrir
      </span>
    </motion.button>
  );
};

export default MysteryBox;
