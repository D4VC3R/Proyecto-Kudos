import React, { useEffect, useRef, useState } from 'react';
import clsx from 'clsx';
import { useReducedMotion } from 'framer-motion';
import { twMerge } from '../../lib/twMerge.js';
import { Star } from 'lucide-react';

const SIZES = {
  md: { box: 'py-1 pl-1.5 pr-2 text-caption', icon: 14 },
  lg: { box: 'py-1.5 pl-2 pr-3 text-small', icon: 16 },
};

// Píldora de Kudos. Si `value` cambia, el número da un salto (`animate-bump`, que el CSS global frena con
// prefers-reduced-motion). `children` sustituye al número cuando hace falta otro formato. Con `showGain`, cada subida
// del valor también hace flotar un «+N» bajo la píldora.
const KudosBadge = ({ value, size = 'md', children, className, showGain = false }) => {
  const previous = useRef(value);
  const [bumping, setBumping] = useState(false);
  const [gain, setGain] = useState(null);
  const reduceMotion = useReducedMotion();
  const { box, icon } = SIZES[size] || SIZES.md;

  useEffect(() => {
    if (previous.current !== value) {
      // Con movimiento reducido no hay `animationend` que lo retire, por eso en ese caso no se pinta el «+N».
      const delta = Number(value) - Number(previous.current);
      previous.current = value;
      setBumping(true);
      if (showGain && !reduceMotion && delta > 0) setGain({ amount: delta, key: Date.now() });
    }
  }, [value]);

  return (
    <span
      onAnimationEnd={(e) => {
        if (e.animationName === 'bump') setBumping(false);
      }}
      className={twMerge(clsx(
        'relative inline-flex items-center gap-1 rounded-full bg-kudos font-bold leading-none tabular-nums whitespace-nowrap text-on-kudos',
        box,
        bumping && 'animate-bump',
        className,
      ))}
    >
      <Star aria-hidden="true" size={icon} fill="currentColor" />
      {children ?? value}
      <span className="sr-only"> kudos</span>
      {gain && (
        <span
          key={gain.key}
          data-kudos-gain
          aria-hidden="true"
          onAnimationEnd={(e) => {
            e.stopPropagation();
            setGain(null);
          }}
          className="pointer-events-none absolute left-1/2 top-full mt-1.5 rounded-full bg-kudos px-2 py-1 text-caption font-bold text-on-kudos shadow-raised animate-fly"
        >
          +{gain.amount}
        </span>
      )}
    </span>
  );
};

export default KudosBadge;
