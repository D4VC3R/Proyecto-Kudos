import React from 'react';
import { NavLink } from 'react-router-dom';
import { adminTabs} from "../../lib/constants.js";
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';



export const AdminNav = () => {
  return (
    <nav className="flex space-x-2 bg-white/60 backdrop-blur-md p-2 rounded-3xl overflow-x-auto shadow-sm ring-1 ring-slate-900/5">
      {adminTabs.map((tab) => {
        const Icon = tab.icon;
        return (
          <NavLink
            key={tab.name}
            to={tab.to}
            className={({ isActive }) => twMerge(
              clsx(
                "flex items-center gap-2 px-5 py-3 rounded-2xl font-bold transition-all whitespace-nowrap",
                isActive
                  ? "bg-blue-600 text-white shadow-md shadow-blue-500/30 ring-2 ring-blue-600 ring-offset-2 ring-offset-slate-50"
                  : "text-slate-600 hover:bg-slate-100 hover:text-slate-900 hover:shadow-sm"
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