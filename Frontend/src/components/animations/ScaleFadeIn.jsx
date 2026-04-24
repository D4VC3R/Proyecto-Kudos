import React from 'react';
import { motion } from 'framer-motion';
import { scaleFadeVariants } from '../../lib/animations';

export const ScaleFadeIn = ({ children, className = '' }) => (
    <motion.div
        variants={scaleFadeVariants}
        initial="hidden"
        animate="visible"
        className={className}
    >
        {children}
    </motion.div>
);