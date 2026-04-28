import React from 'react';
import {motion} from 'framer-motion';
import {staggereContainerVariants} from '../../lib/animations';

// Componente que envuelve cualquier contenido para aplicar una animación de aparición escalonada a sus hijos.
// Hace que los elementos aparezcan uno tras otro con un pequeño retraso entre ellos.
export const StaggerGrid = ({children, className = ''}) => {
    return (
        <motion.div
            variants={staggereContainerVariants}
            initial="hidden"
            animate="visible"
            className={className}
        >
            {children}
        </motion.div>
    );
};