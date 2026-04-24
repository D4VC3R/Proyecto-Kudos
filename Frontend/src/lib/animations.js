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

export const depthTransitionVariants = {
    initial: {
        opacity: 0,
        scale: 0.6
    },
    animate: {
        opacity: 1,
        scale: 1,
        transition: {
            type: 'spring',
            stiffness: 260,
            damping: 20,
        }
    },
    exit: {
        opacity: 0,
        scale: 0.6,
        transition: {
            duration: 0.3,
            ease: 'easeInOut'
        }
    }
};

export const fadeUpVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.4 }
    }
};

// Para el botón de "Volver arriba"
export const popInVariants = {
    hidden: { opacity: 0, scale: 0.8 },
    visible: { opacity: 1, scale: 1 },
    exit: { opacity: 0, scale: 0.8 }
};

export const slideTransitionVariants = {
    initial: (direction) => ({
        opacity: 0,
        x: direction === 'left' ? -20 : 20
    }),
    animate: {
        opacity: 1,
        x: 0,
        transition: { duration: 0.2 }
    },
    exit: (direction) => ({
        opacity: 0,
        x: direction === 'left' ? 20 : -20,
        transition: { duration: 0.2 }
    })
};
export const hoverFadeTextVariants = {
    initial: { y: 10, opacity: 0.9 },
    hover: {
        y: 0,
        opacity: 1,
        transition: { duration: 0.2 }
    }
};