import React from 'react';
import { motion } from 'framer-motion';
import { scaleFadeVariants } from '../../lib/animations';

// Componente que envuelve cualquier contenido para aplicar una animación de entrada que combina escalado y desvanecimiento.
export const ScaleFadeIn = ({ children, className = '' }) => (
    <motion.div
        variants={scaleFadeVariants}
        initial="hidden"
        animate="visible"
        className={className}
    >
        {children}
    </motion.div>
);