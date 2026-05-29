import React from 'react';
import { motion } from 'framer-motion';
import { hoverFadeTextVariants } from '../../lib/animations';

// Componente que envuelve cualquier texto para aplicar una animación de desvanecimiento al hacer hover sobre él.
const HoverFadeText = ({ children, className = '' }) => {
    return (
        <motion.div
            variants={hoverFadeTextVariants}
            className={className}
        >
            {children}
        </motion.div>
    );
};

export default HoverFadeText;