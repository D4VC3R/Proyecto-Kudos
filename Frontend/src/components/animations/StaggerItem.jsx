import React from 'react';
import { motion } from 'framer-motion';
import { staggerItemVariants, alternateStaggerItemVariants } from '../../lib/animations';

// Componente hijo de StaggerGrid polimórfico.
export const StaggerItem = ({
                                children,
                                className = '',
                                alternate = false,
                                index = 0
                            }) => {

    const selectedVariants = alternate ? alternateStaggerItemVariants : staggerItemVariants;

    return (
      <motion.div
        variants={selectedVariants}
        custom={index}
        className={className}
      >
          {children}
      </motion.div>
    );
}