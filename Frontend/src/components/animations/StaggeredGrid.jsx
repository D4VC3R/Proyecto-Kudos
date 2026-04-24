import React from 'react';
import { motion } from 'framer-motion';
import { staggereContainerVariants } from '../../lib/animations';

export const StaggeredGrid = ({ children, className = '' }) => {
    return (
        <motion.div
            variants={staggereContainerVariants}
            initial="hidden"
            animate="visible"
            className={className}
        >
            {children}
        </motion.div>
    );
};