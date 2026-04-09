export const staggereContainerVariants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: {
            staggerChildren: 0.1,
        }
    }
};

export const bouncyCardVariants = {
    hidden: { opacity: 0, y: 50 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { type: 'spring', stiffness: 100, damping: 15 }
    },
    hover: {
        y: -8,
        scale: 1.02,
        transition: { type: 'spring', stiffness: 400, damping: 10 }
    },
    tap: {
        scale: 0.95, // Pequeño efecto de hundimiento al hacer clic (muy de videojuegos)
    }
};