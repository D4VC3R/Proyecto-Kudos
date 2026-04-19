import React from 'react';
import clsx from 'clsx';
import { motion } from 'framer-motion';
export const StatCard = ({ title, value, icon: Icon, colorClass, delay = 0 }) => {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay }}
      className="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow"
    >
      <div className={clsx("flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl shadow-inner", colorClass)}>
        <Icon size={32} strokeWidth={2.5} />
      </div>
      <div className="flex flex-col">
        <p className="text-sm font-bold text-slate-500 uppercase tracking-widest">{title}</p>
        <p className="text-3xl font-black text-slate-900 tracking-tight">{value}</p>
      </div>
    </motion.div>
  );
};
