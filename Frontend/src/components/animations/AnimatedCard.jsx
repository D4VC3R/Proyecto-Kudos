import React from 'react';
import { motion } from 'framer-motion';
import { cardTransitionVariants } from '../../lib/animations';

export const AnimatedCard = ({ children, className = '', ...props }) => {
  return (
    <motion.div
      layout
      variants={cardTransitionVariants}
      initial="initial"
      animate="animate"
      exit="exit"
      className={className}
      {...props}
    >
      {children}
    </motion.div>
  );
};