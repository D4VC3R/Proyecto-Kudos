import React from 'react';
import { motion, useReducedMotion } from 'framer-motion';
import { fadeUpVariants } from '../../lib/animations';

// Componente que envuelve cualquier contenido para crear una animación de entrada para algunas páginas.
// Utiliza fadeUpVariants para crear un efecto de desvanecimiento y movimiento hacia arriba al cargar la página.
const FadeUp = ({ children, className = '' }) => {
    const reduce = useReducedMotion();

    return (
        <motion.div
            variants={fadeUpVariants}
            initial={reduce ? false : 'hidden'}
            animate="visible"
            className={className}
        >
            {children}
        </motion.div>
    );
};

export default FadeUp;