/**
 * Curvas Bézier globales para mantener la identidad visual de la app.
 * smooth: Aceleración natural, frena suavemente al final (ideal para entradas).
 * snappy: Rápido y con energía, similar a Material Design (ideal para interacciones rápidas).
 */
export const EASING = {
    smooth: [0.22, 1, 0.36, 1],
    snappy: [0.4, 0, 0.2, 1],
};

/* -----------------------------------------------------------------
 * COMPONENTES DE LISTAS Y GRUPOS (STAGGER)
 * ----------------------------------------------------------------- */

export const staggerContainerVariants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: {
            delayChildren: 0.1,
            staggerChildren: 0.1,
        }
    }
};

export const staggerItemVariants = {
    hidden: { opacity: 0, y: 15 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.4, ease: EASING.smooth }
    },
    exit: {
        opacity: 0,
        y: 15,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const alternateStaggerItemVariants = {
    hidden: (index) => ({
        opacity: 0,
        x: index % 2 === 0 ? -50 : 50,
    }),
    visible: {
        opacity: 1,
        x: 0,
        transition: { type: 'spring', damping: 20, stiffness: 100 }
    },
    exit: (index) => ({
        opacity: 0,
        x: index % 2 === 0 ? -50 : 50,
        transition: { duration: 0.2, ease: "easeIn" }
    })
};

/* -----------------------------------------------------------------
 * TARJETAS Y ELEMENTOS DE INTERFAZ
 * ----------------------------------------------------------------- */

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
        scale: 0.95,
    },
    exit: {
        opacity: 0,
        y: 50,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const scaleFadeVariants = {
    hidden: { opacity: 0, scale: 0.95 },
    visible: {
        opacity: 1,
        scale: 1,
        transition: { duration: 0.3, ease: EASING.smooth }
    },
    exit: {
        opacity: 0,
        scale: 0.95,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const hoverFadeTextVariants = {
    initial: { y: 10, opacity: 0.9 },
    hover: {
        y: 0,
        opacity: 1,
        transition: { duration: 0.2, ease: EASING.snappy }
    }
};

/* -----------------------------------------------------------------
 * TRANSICIONES GENÉRICAS DE LAYOUT
 * ----------------------------------------------------------------- */

export const fadeVariants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: { duration: 0.3, ease: EASING.smooth }
    },
    exit: {
        opacity: 0,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const fadeUpVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.4, ease: EASING.smooth }
    },
    exit: {
        opacity: 0,
        y: 20,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const popInVariants = {
    hidden: { opacity: 0, scale: 0.8 },
    visible: {
        opacity: 1,
        scale: 1,
        transition: { type: 'spring', stiffness: 300, damping: 20 }
    },
    exit: {
        opacity: 0,
        scale: 0.8,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const slideTransitionVariants = {
    initial: (direction) => ({
        opacity: 0,
        x: direction === 'left' ? -20 : 20
    }),
    animate: {
        opacity: 1,
        x: 0,
        transition: { duration: 0.4, ease: EASING.smooth }
    },
    exit: (direction) => ({
        opacity: 0,
        x: direction === 'left' ? 20 : -20,
        transition: { duration: 0.2, ease: "easeIn" }
    })
};

export const depthTransitionVariants = {
    initial: { opacity: 0, scale: 0.6 },
    animate: {
        opacity: 1,
        scale: 1,
        transition: { type: 'spring', stiffness: 260, damping: 20 }
    },
    exit: {
        opacity: 0,
        scale: 0.6,
        transition: { duration: 0.3, ease: EASING.smooth }
    }
};

export const modalPanelVariants = {
    hidden: { opacity: 0, scale: 0.95, y: 10 },
    visible: {
        opacity: 1,
        scale: 1,
        y: 0,
        transition: { type: 'spring', damping: 25, stiffness: 300 }
    },
    exit: {
        opacity: 0,
        scale: 0.95,
        y: 10,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const infiniteScrollVariants = {
    animate: (duration) => ({
        x: ['0%', '-50%'],
        transition: { ease: 'linear', duration: duration, repeat: Infinity }
    })
};

/* -----------------------------------------------------------------
 * GAMIFICACIÓN Y RECOMPENSAS
 * ----------------------------------------------------------------- */

export const gamifiedEntryVariants = {
    hidden: { opacity: 0, scale: 0.8 },
    visible: {
        opacity: 1,
        scale: 1,
        transition: { type: 'spring', stiffness: 300, damping: 20 }
    },
    exit: {
        opacity: 0,
        scale: 0,
        rotate: -15,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const rewardRevealVariants = {
    hidden: { opacity: 0, scale: 0.5, y: 20 },
    visible: {
        opacity: 1,
        scale: 1,
        y: 0,
        transition: {
            type: 'spring',
            stiffness: 400,
            damping: 15,
            delayChildren: 0.1,
            staggerChildren: 0.15
        }
    },
    exit: {
        opacity: 0,
        scale: 0.8,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

export const springPopVariants = {
    hidden: { scale: 0, opacity: 0, y: 20 },
    visible: {
        scale: 1,
        opacity: 1,
        y: 0,
        transition: { type: "spring", stiffness: 300, damping: 15 }
    },
    exit: {
        scale: 0,
        opacity: 0,
        y: 20,
        transition: { duration: 0.2, ease: "easeIn" }
    }
};

/* --- Animaciones en bucle (Loops) --- */

export const floatingLoopVariants = {
    animate: {
        y: [0, -10, 0],
        transition: { repeat: Infinity, duration: 2, ease: 'easeInOut' }
    }
};

export const pulseLoopVariants = {
    animate: {
        scale: [1, 1.2, 1],
        opacity: [0.5, 1, 0.5],
        transition: { repeat: Infinity, duration: 1.5, ease: 'easeInOut' }
    }
};