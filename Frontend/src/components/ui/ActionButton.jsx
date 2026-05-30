import React from 'react';
import {Link} from 'react-router-dom';
import clsx from 'clsx';
import BouncyCard from "../animations/BouncyCard.jsx";

const ActionButton = ({title, description, icon: Icon, to, color = 'blue'}) => {
  const colorStyles = {
    blue: 'bg-gradient-to-br from-blue-500 to-blue-700 shadow-blue-500/30 ring-blue-400',
    red: 'bg-gradient-to-br from-red-500 to-red-700 shadow-red-500/30 ring-red-400',
    green: 'bg-gradient-to-br from-green-500 to-green-700 shadow-green-500/30 ring-green-400',
    yellow: 'bg-gradient-to-br from-yellow-500 to-yellow-700 shadow-yellow-500/30 ring-accent',
    purple: 'bg-gradient-to-br from-purple-500 to-purple-700 shadow-purple-500/30 ring-purple-400',
  };

  return (
    <Link to={to} className="block w-full h-full">
      <BouncyCard
        className={clsx(
          'relative flex h-full items-center overflow-hidden rounded-3xl text-text-btn shadow-xl ring-1 transition-shadow hover:shadow-2xl',
          'p-5 md:p-4 lg:p-6',
          colorStyles[color]
        )}
      >
        <div
          className="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-12 transition-transform duration-700 ease-out hover:translate-x-full"/>

        <div className="relative z-10 flex flex-1 flex-col min-w-0">
          <h3 className="text-xl sm:text-lg lg:text-2xl font-black tracking-tight drop-shadow-md leading-tight break-words">
            {title}
          </h3>
          <p className="mt-1 text-sm md:text-xs lg:text-sm font-medium text-text-btn/80">
            {description}
          </p>
        </div>

        <div className="relative z-10 ml-3 lg:ml-4 shrink-0 rounded-2xl bg-white/20 p-3 lg:p-4 backdrop-blur-sm">
          <Icon className="h-6 w-6 lg:h-8 lg:w-8 text-text-btn drop-shadow-sm"/>
        </div>
      </BouncyCard>
    </Link>
  );
};

export default ActionButton;