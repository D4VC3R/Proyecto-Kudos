import React from 'react';
import { motion } from 'framer-motion';
import { modalPanelVariants } from '../../lib/animations';

export const ModalTransition = ({ children, className = '' }) => {
  return (
    <motion.div
      className={className}
      variants={modalPanelVariants}
      initial="hidden"
      animate="visible"
      exit="exit"
    >
      {children}
    </motion.div>
  );
};