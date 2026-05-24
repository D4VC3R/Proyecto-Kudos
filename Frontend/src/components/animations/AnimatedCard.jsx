import React from 'react';
import { motion } from 'framer-motion';
import { scaleFadeVariants } from '../../lib/animations';

export const AnimatedCard = ({ children, className = '', ...props }) => {
  return (
    <motion.div
      layout
      variants={scaleFadeVariants}
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