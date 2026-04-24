import React from 'react';
import { motion } from 'framer-motion';
import { fadeUpVariants } from '../../lib/animations';

export const FadeUp = ({ children, className = '' }) => {
    return (
        <motion.div
            variants={fadeUpVariants}
            initial="hidden"
            animate="visible"
            className={className}
        >
            {children}
        </motion.div>
    );
};