import React from 'react';
import { motion } from 'framer-motion';
import { cardTransitionVariants } from '../../lib/animations';

export const AnimatedCard = ({ children, className = '' }) => {
  return (
    <motion.div
      layout
      variants={cardTransitionVariants}
      initial="initial"
      animate="animate"
      exit="exit"
      className={className}
    >
      {children}
    </motion.div>
  );
};