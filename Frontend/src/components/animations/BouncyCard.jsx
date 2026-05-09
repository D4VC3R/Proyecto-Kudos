import React from 'react';
import { motion } from 'framer-motion';
import { bouncyCardVariants } from '../../lib/animations';

// Componente que envuelve cualquier contenido para aplicar una animación de rebote al interactuar con él. 
// Utiliza bouncyCardVariants para crear un efecto de rebote al hacer hover o tap sobre el elemento.
export const BouncyCard = ({ children, className = '', onClick }) => {
    return (
        <motion.article
            variants={bouncyCardVariants}
            whileHover="hover"
            whileTap="tap"
            onClick={onClick}
            className={className}
        >
            {children}
        </motion.article>
    );
};