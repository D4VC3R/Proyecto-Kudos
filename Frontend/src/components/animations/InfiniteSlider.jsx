import React from 'react';
import { motion } from 'framer-motion';
import { infiniteSliderVariants } from './../../lib/animations.js';

const InfiniteSlider = ({ children, duration, className = '' }) => {
  return (
    <motion.div
      className={className}
      variants={infiniteSliderVariants}
      animate="animate"
      custom={duration}
    >
      {children}
    </motion.div>
  );
};

export default InfiniteSlider;