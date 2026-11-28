import React from 'react';
import { motion, useMotionValue, useTransform } from 'framer-motion';

// Desplazamiento (px) o velocidad (px/s) a partir de los que un arrastre cuenta como respuesta.
const SWIPE_OFFSET = 100;
const SWIPE_VELOCITY = 500;

// Tarjeta del ítem en el paso "¿Lo conoces?" (F2-04): derecha = Sí, izquierda = No. Con `enabled` a false no se arrastra.
const SwipeVoteCard = ({ enabled, onSwipeRight, onSwipeLeft, children }) => {
  const x = useMotionValue(0);
  const rotate = useTransform(x, [-200, 200], [-12, 12]);
  const yesOpacity = useTransform(x, [0, SWIPE_OFFSET], [0, 1]);
  const noOpacity = useTransform(x, [-SWIPE_OFFSET, 0], [1, 0]);

  const handleDragEnd = (_event, info) => {
    if (info.offset.x > SWIPE_OFFSET || info.velocity.x > SWIPE_VELOCITY) onSwipeRight();
    else if (info.offset.x < -SWIPE_OFFSET || info.velocity.x < -SWIPE_VELOCITY) onSwipeLeft();
  };

  return (
    <motion.div
      drag={enabled ? 'x' : false}
      dragSnapToOrigin
      onDragEnd={handleDragEnd}
      style={{ x, rotate }}
      className={`relative w-full [&_img]:pointer-events-none ${enabled ? 'cursor-grab active:cursor-grabbing' : ''}`}
    >
      {children}
      {enabled && (
        <>
          <motion.span style={{ opacity: yesOpacity }} aria-hidden="true" className="pointer-events-none absolute top-4 left-4 z-20 -rotate-12 rounded-md border-4 border-success bg-surface/90 px-3 py-1 font-display text-2xl font-semibold uppercase text-success">Sí</motion.span>
          <motion.span style={{ opacity: noOpacity }} aria-hidden="true" className="pointer-events-none absolute top-4 right-4 z-20 rotate-12 rounded-md border-4 border-danger bg-surface/90 px-3 py-1 font-display text-2xl font-semibold uppercase text-danger">No</motion.span>
        </>
      )}
    </motion.div>
  );
};

export default SwipeVoteCard;
