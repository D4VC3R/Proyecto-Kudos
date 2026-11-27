import React from 'react';
import { motion, useReducedMotion } from 'framer-motion';
import { fadeVariants } from '../../lib/animations';
const Fade = ({ children, className = '', onClick }) => {
  const reduce = useReducedMotion();

  return (
    <motion.div
      className={className}
      variants={fadeVariants}
      initial={reduce ? false : 'hidden'}
      animate="visible"
      exit="exit"
      onClick={onClick}
      aria-hidden="true"
    >
      {children}
    </motion.div>
  );
};

export default Fade;