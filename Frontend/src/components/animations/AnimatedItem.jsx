import React from 'react';
import { motion } from 'framer-motion';
import { depthTransitionVariants } from '../../lib/animations.js';

// Componente que envuelve cada ítem votable para aplicar animaciones de entrada y salida con framer-motion. 
// Utiliza depthTransitionVariants para crear un efecto de profundidad al cambiar de ítem.
export const AnimatedItem = ({ children, itemKey }) => {
    return (
        <motion.div
            key={itemKey}
            variants={depthTransitionVariants}
            initial="initial"
            animate="animate"
            exit="exit"
            className="w-full flex justify-center"
        >
            {children}
        </motion.div>
    );
};