import React from 'react';
import { motion } from 'framer-motion';
import { depthTransitionVariants } from './../../lib/animations.js';

export const AnimatedItem = ({ children, itemKey }) => {
    return (
        <motion.div
            key={itemKey}
            variants={depthTransitionVariants}
            initial="initial"
            animate="animate"
            exit="exit"
            className="w-full flex justify-center"
        >
            {children}
        </motion.div>
    );
};