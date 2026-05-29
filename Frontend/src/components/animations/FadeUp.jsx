import React from 'react';
import { motion } from 'framer-motion';
import { fadeUpVariants } from '../../lib/animations';

// Componente que envuelve cualquier contenido para crear una animación de entrada para algunas páginas.
// Utiliza fadeUpVariants para crear un efecto de desvanecimiento y movimiento hacia arriba al cargar la página.
const FadeUp = ({ children, className = '' }) => {
    return (
        <motion.div
            variants={fadeUpVariants}
            initial="hidden"
            animate="visible"
            className={className}
        >
            {children}
        </motion.div>
    );
};

export default FadeUp;