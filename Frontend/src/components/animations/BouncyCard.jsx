import React from 'react';
import { motion } from 'framer-motion';
import { bouncyCardVariants } from '../../lib/animations';

export const BouncyCard = ({ children, className = '' }) => {
    return (
        <motion.article
            variants={bouncyCardVariants}
            whileHover="hover"
            whileTap="tap"
            className={className}
        >
            {children}
        </motion.article>
    );
};