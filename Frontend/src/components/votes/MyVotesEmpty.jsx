import React from 'react';
import { motion } from 'framer-motion';
import { ThumbsUp } from 'lucide-react';
import { Link } from 'react-router-dom';

export const MyVotesEmpty = () => {
  return (
    <motion.div
      initial={{ opacity: 0, scale: 0.95 }}
      animate={{ opacity: 1, scale: 1 }}
      className="flex flex-col items-center justify-center p-12 text-center bg-white rounded-3xl border border-slate-200 shadow-sm"
    >
      <div className="flex h-20 w-20 items-center justify-center rounded-3xl bg-blue-50 text-blue-500 mb-6 relative">
        <ThumbsUp size={40} className="relative z-10" />
      </div>

      <h3 className="text-xl font-bold text-slate-900 mb-2">No tienes votos registrados</h3>
      <p className="text-slate-500 max-w-sm mb-6 font-medium">
        An no has valorado ningn ítem.
      </p>

      <Link
        to="/"
        className="inline-flex -translate-y-1 items-center gap-2 rounded-2xl bg-blue-600 px-6 py-3 font-bold text-white shadow-lg transition-transform hover:-translate-y-2 active:translate-y-0"
      >
        <ThumbsUp size={18} />
        Comenzar a votar
      </Link>
    </motion.div>
  );
};

