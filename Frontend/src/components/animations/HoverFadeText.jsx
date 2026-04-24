import React from 'react';
import { motion } from 'framer-motion';
import { hoverFadeTextVariants } from '../../lib/animations';

export const HoverFadeText = ({ children, className = '' }) => {
    return (
        <motion.div
            variants={hoverFadeTextVariants}
            className={className}
        >
            {children}
        </motion.div>
    );
};