import React from 'react';
import AnimatedCard  from '../animations/AnimatedCard';
import clsx from 'clsx';

const StatCard = ({ icon: Icon, value, label, containerClass = "bg-background border-slate-100", iconColor = "text-blue-500", valueClass = "text-3xl" }) => {
  return (
    <AnimatedCard className={clsx("border p-5 rounded-3xl flex flex-col items-center justify-center text-center", containerClass)}>
      <Icon className={clsx("mb-2", iconColor)} size={32} />
      <span className={clsx("font-black text-slate-800", valueClass)}>{value || 0}</span>
      <span className="text-xs font-bold text-nav-item uppercase mt-1">{label}</span>
    </AnimatedCard>
  );
};

export default StatCard;
