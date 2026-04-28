import React from 'react';
import { motion } from 'framer-motion';
import { slideTransitionVariants } from '../../lib/animations';

// Componente que envuelve cualquier contenido para aplicar una animación de transición de deslizado al entrar o salir de la vista.
export const SlideTransition = ({ children, direction = 'left', animationKey, className = '' }) => {
    return (
        <motion.div
            key={animationKey}
            custom={direction}
            variants={slideTransitionVariants}
            initial="initial"
            animate="animate"
            exit="exit"
            className={className}
        >
            {children}
        </motion.div>
    );
};