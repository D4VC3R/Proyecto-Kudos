import React from 'react';
import { NavLink } from 'react-router-dom';
import { twMerge } from 'tailwind-merge';
import clsx from 'clsx';
import { VARIANTS } from "../../lib/buttonStyles.js";
import GlobalSearch from "./GlobalSearch.jsx";

const NavBarMobile = ({ id, isOpen, onClose, navItems }) => {
  if (!isOpen) return null;

  const mobileBaseStyles = "flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all";

  return (
    <div id={id} className="absolute top-full left-0 w-full bg-surface border-y border-border shadow-xl md:hidden py-4 px-4 flex flex-col gap-2 z-40">
      <GlobalSearch onNavigate={onClose} className="mb-2" />
      {navItems.map((item) => {
        const Icon = item.icon;
        return (
          <NavLink
            key={item.path}
            to={item.path}
            onClick={onClose}
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

export default NavBarMobile;