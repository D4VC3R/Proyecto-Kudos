import React from 'react';
import {motion} from 'framer-motion';
import {staggerContainerVariants} from '../../lib/animations';


const StaggerGrid = ({children, className = ''}) => {
    return (
        <motion.div
            variants={staggerContainerVariants}
            initial="hidden"
            animate="visible"
            className={className}
        >
            {children}
        </motion.div>
    );
};

export default StaggerGrid;