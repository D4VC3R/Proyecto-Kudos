import React from 'react';
import clsx from 'clsx';
import { FadeUp } from '../animations/FadeUp.jsx';

export const StatCard = ({ title, value, icon: Icon, colorClass }) => {
  return (
    <FadeUp
      className="bg-surface rounded-3xl p-6 border border-border shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow"
    >
      <div className={clsx("flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl shadow-inner", colorClass)}>
        <Icon size={32} strokeWidth={2.5} />
      </div>
      <div className="flex flex-col">
        <p className="text-sm font-bold text-text-normal uppercase tracking-widest">{title}</p>
        <p className="text-3xl font-black text-text-highlight tracking-tight">{value}</p>
      </div>
    </FadeUp>
  );
};
