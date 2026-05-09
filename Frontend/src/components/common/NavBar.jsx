import React, { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { Grid, Trophy, Menu, X, ShieldPlus } from 'lucide-react';
import clsx from 'clsx';
import { Button } from './Button';

export const NavBar = () => {
  const [isOpen, setIsOpen] = useState(false);

  const navItems = [
    { name: 'Categorías', path: '/', icon: Grid },
    { name: 'Ranking', path: '/ranking', icon: Trophy },
    { name: 'Administración', path: 'admin', icon: ShieldPlus }
  ];

  return (
    <>
      {/* Menú Desktop */}
      <nav className="hidden md:flex flex-1 items-center justify-center gap-2 lg:gap-6 mx-4">
        {navItems.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) => clsx(
              "flex items-center gap-2 px-4 py-2 rounded-xl font-bold transition-all",
              isActive 
                ? "bg-blue-50 text-blue-600 shadow-sm ring-1 ring-blue-100" 
                : "text-slate-500 hover:bg-slate-100 hover:text-slate-900"
            )}
          >
            <item.icon size={18} />
            <span>{item.name}</span>
          </NavLink>
        ))}
      </nav>

      {/* Botón Menú Mobile */}
      <div className="flex md:hidden items-center">
        <Button
          onClick={() => setIsOpen(!isOpen)}
          variant="outline"
          color="neutral"
          size="iconSm"
          className="rounded-lg bg-white"
          aria-label="Menu"
          icon={isOpen ? X : Menu}
        />
      </div>

      {/* Dropdown Mobile */}
      {isOpen && (
        <div className="absolute top-[72px] left-0 w-full bg-white border-b border-slate-200 shadow-xl md:hidden py-4 px-4 flex flex-col gap-2 z-40">
          {navItems.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              onClick={() => setIsOpen(false)}
              className={({ isActive }) => clsx(
                "flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all",
                isActive 
                  ? "bg-blue-50 text-blue-600 shadow-sm ring-1 ring-blue-100" 
                  : "text-slate-500 hover:bg-slate-100 hover:text-slate-900"
              )}
            >
              <item.icon size={20} />
              <span>{item.name}</span>
            </NavLink>
          ))}
        </div>
      )}
    </>
  );
};
