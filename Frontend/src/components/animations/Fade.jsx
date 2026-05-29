import React from 'react';
import { motion } from 'framer-motion';
import { fadeVariants } from '../../lib/animations';
const Fade = ({ children, className = '', onClick }) => {
  return (
    <motion.div
      className={className}
      variants={fadeVariants}
      initial="hidden"
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