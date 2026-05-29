import React from 'react';
import {motion} from 'framer-motion';
import {staggerItemVariants, alternateStaggerItemVariants} from '../../lib/animations';

const StaggerItem = ({
                       children,
                       className = '',
                       alternate = false,
                       index = 0,
                       onClick
                     }) => {

  const selectedVariants = alternate ? alternateStaggerItemVariants : staggerItemVariants;

  return (
    <motion.div
      variants={selectedVariants}
      custom={index}
      className={className}
      onClick={onClick}
    >
      {children}
    </motion.div>
  );
}

export default StaggerItem;