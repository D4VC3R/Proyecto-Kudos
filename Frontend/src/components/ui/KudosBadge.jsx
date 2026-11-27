import React, { useEffect, useRef, useState } from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';
import { Star } from 'lucide-react';

const SIZES = {
  md: { box: 'py-1 pl-1.5 pr-2 text-caption', icon: 14 },
  lg: { box: 'py-1.5 pl-2 pr-3 text-small', icon: 16 },
};

// Píldora de Kudos. Si `value` cambia, el número da un salto (`animate-bump`, que el CSS global frena con
// prefers-reduced-motion). `children` sustituye al número cuando hace falta otro formato.
const KudosBadge = ({ value, size = 'md', children, className }) => {
  const previous = useRef(value);
  const [bumping, setBumping] = useState(false);
  const { box, icon } = SIZES[size] || SIZES.md;

  useEffect(() => {
    if (previous.current !== value) {
      previous.current = value;
      setBumping(true);
    }
  }, [value]);

  return (
    <span
      onAnimationEnd={() => setBumping(false)}
      className={twMerge(clsx(
        'inline-flex items-center gap-1 rounded-full bg-kudos font-bold leading-none tabular-nums whitespace-nowrap text-on-kudos',
        box,
        bumping && 'animate-bump',
        className,
      ))}
    >
      <Star aria-hidden="true" size={icon} fill="currentColor" />
      {children ?? value}
      <span className="sr-only"> kudos</span>
    </span>
  );
};

export default KudosBadge;
