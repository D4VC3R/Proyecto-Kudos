import React from 'react';
import { motion } from 'framer-motion';
import { popInVariants } from '../../lib/animations';

export const PopButton = ({ children, onClick, className = '', ariaLabel = '' }) => {
    return (
        <motion.button
            variants={popInVariants}
            initial="hidden"
            animate="visible"
            exit="exit"
            onClick={onClick}
            className={className}
            aria-label={ariaLabel}
        >
            {children}
        </motion.button>
    );
};