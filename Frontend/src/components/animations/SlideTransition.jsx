import React from 'react';
import { motion } from 'framer-motion';
import { slideTransitionVariants } from '../../lib/animations';

export const SlideTransition = ({ children, direction = 'left', animationKey, className = '' }) => {
    return (
        <motion.div
            key={animationKey}
            custom={direction}
            variants={slideTransitionVariants}
            initial="initial"
            animate="animate"
            exit="exit"
            className={className}
        >
            {children}
        </motion.div>
    );
};