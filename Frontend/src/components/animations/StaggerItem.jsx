import React from 'react';
import {motion} from 'framer-motion';
import {staggerItemVariants} from '../../lib/animations';

export const StaggerItem = ({children, className = ''}) => {
    return (
        <motion.div variants={staggerItemVariants} className={className}>
            {children}
        </motion.div>
    );
}