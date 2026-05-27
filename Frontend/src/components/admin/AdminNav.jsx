import React from 'react';
import { NavLink } from 'react-router-dom';
import { adminTabs} from "../../lib/constants.js";
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import {VARIANTS} from "../../lib/buttonStyles.js";

export const AdminNav = () => {
  const baseNavStyles = "flex items-center gap-2 px-5 py-3 rounded-2xl font-bold transition-all whitespace-nowrap";

  return (
    <nav className="flex space-x-2 bg-surface/60 backdrop-blur-md p-2 rounded-3xl overflow-x-auto shadow-sm ring-1 ring-slate-900/5">
      {adminTabs.map((tab) => {
        const Icon = tab.icon;
        return (
          <NavLink
            key={tab.name}
            to={tab.to}
            className={({ isActive }) => twMerge(
              clsx(
                baseNavStyles,
                isActive ? VARIANTS.ring.navActive : VARIANTS.ring.navInactive
              )
            )}
          >
            <Icon size={18} strokeWidth={2.5} />
            {tab.name}
          </NavLink>
        );
      })}
    </nav>
  );
};