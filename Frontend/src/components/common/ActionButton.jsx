import React from 'react';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
import { bouncyCardVariants } from '../../lib/animations';

const ActionButton = ({ title, description, icon: Icon, to, color = 'blue' }) => {
  const colorStyles = {
    blue: 'bg-gradient-to-br from-blue-500 to-blue-700 shadow-blue-500/30 ring-blue-400',
    red: 'bg-gradient-to-br from-red-500 to-red-700 shadow-red-500/30 ring-red-400',
  };

  return (
    <Link to={to} className="block w-full">
      <motion.div
        variants={bouncyCardVariants}
        whileHover="hover"
        whileTap="tap"
        className={clsx(
          'relative flex items-center overflow-hidden rounded-3xl p-6 text-white shadow-xl ring-1 transition-shadow hover:shadow-2xl',
          colorStyles[color]
        )}
      >

        <div className="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-12 transition-transform duration-700 ease-out hover:translate-x-full" />

        <div className="relative z-10 flex flex-1 flex-col">
          <h3 className="text-2xl font-black tracking-tight drop-shadow-md">{title}</h3>
          <p className="mt-1 text-sm font-medium text-white/80">{description}</p>
        </div>

        <div className="relative z-10 ml-4 rounded-2xl bg-white/20 p-4 backdrop-blur-sm">
          <Icon size={32} className="text-white drop-shadow-sm" />
        </div>
      </motion.div>
    </Link>
  );
};

export default ActionButton;