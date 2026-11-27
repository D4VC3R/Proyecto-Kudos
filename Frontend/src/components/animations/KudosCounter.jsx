import React, { useEffect } from 'react';
import { motion, useReducedMotion, useSpring, useTransform } from 'framer-motion';

const KudosCounter = ({ value = 0 }) => {
  const reduce = useReducedMotion();
  const spring = useSpring(value, { mass: 0.8, stiffness: 75, damping: 15 });
  const display = useTransform(spring, (current) => Math.round(current));

  useEffect(() => {
    spring.set(value);
  }, [value, spring]);

  // Sin movimiento, el número aparece directamente en su valor final.
  if (reduce) {
    return <span className="inline-block tabular-nums">{value}</span>;
  }

  return (
    <motion.span className="inline-block tabular-nums">
      {display}
    </motion.span>
  );
};

export default KudosCounter;

