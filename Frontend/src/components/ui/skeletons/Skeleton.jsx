import React from 'react';
import clsx from 'clsx';
import { twMerge } from '../../../lib/twMerge.js';

const Skeleton = ({ className }) => (
  <div aria-hidden="true" className={twMerge(clsx("bg-line animate-pulse rounded-md", className))} />
);

export default Skeleton;
