import React from 'react';
import { motion } from 'framer-motion';
import { popInVariants } from '../../lib/animations';

// Componente que envuelve cualquier botón para aplicar una animación de entrada tipo "pop-in".
const PopButton = ({ children, onClick, className = '', ariaLabel = '' }) => {
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

export default PopButton;