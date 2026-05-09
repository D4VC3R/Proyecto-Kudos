import React from 'react';
import { NavLink } from 'react-router-dom';
import { twMerge } from 'tailwind-merge';
import clsx from 'clsx';
import { VARIANTS } from "../../lib/buttonStyles.js";

export const NavBarMobile = ({ isOpen, setIsOpen, navItems }) => {
  if (!isOpen) return null;

  const mobileBaseStyles = "flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all";

  return (
    <div className="absolute top-[72px] left-0 w-full bg-white border-b border-slate-200 shadow-xl md:hidden py-4 px-4 flex flex-col gap-2 z-40">
      {navItems.map((item) => {
        const Icon = item.icon;
        return (
          <NavLink
            key={item.path}
            to={item.path}
            onClick={() => setIsOpen(false)} // Cerramos el menú al navegar
            className={({ isActive }) => twMerge(
              clsx(
                mobileBaseStyles,
                isActive ? VARIANTS.tab.active : VARIANTS.tab.inactive
              )
            )}
          >
            <Icon size={20} />
            <span>{item.name}</span>
          </NavLink>
        );
      })}
    </div>
  );
};