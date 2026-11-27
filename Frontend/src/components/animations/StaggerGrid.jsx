import React from 'react';
import {motion, useReducedMotion} from 'framer-motion';
import {staggerContainerVariants} from '../../lib/animations';


const StaggerGrid = ({children, className = '', ...props}) => {
    const reduce = useReducedMotion();

    return (
        <motion.div
            variants={staggerContainerVariants}
            initial={reduce ? false : 'hidden'}
            animate="visible"
            className={className}
            {...props}
        >
            {children}
        </motion.div>
    );
};

export default StaggerGrid;