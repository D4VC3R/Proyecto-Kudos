import React from 'react';
import clsx from 'clsx';

export const Skeleton = ({ className }) => (
  <div className={clsx("bg-slate-200 animate-pulse rounded-xl", className)} />
);