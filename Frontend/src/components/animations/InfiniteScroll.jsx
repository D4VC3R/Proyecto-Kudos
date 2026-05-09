import React from 'react';
import { motion } from 'framer-motion';
import { infiniteScrollVariants } from './../../lib/animations.js';

export const InfiniteScroll = ({ children, duration, className = '' }) => {
  return (
    <motion.div
      className={className}
      variants={infiniteScrollVariants}
      animate="animate"
      custom={duration}
    >
      {children}
    </motion.div>
  );
};