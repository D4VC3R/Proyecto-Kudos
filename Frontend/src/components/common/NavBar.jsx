import React, { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { Grid, Trophy, Menu, X, ShieldPlus } from 'lucide-react';
import { twMerge } from 'tailwind-merge';
import clsx from 'clsx';
import { Button } from './Button';
import { VARIANTS } from "../../lib/buttonStyles.js";
import {NavBarMobile} from "./NavBarMobile.jsx";

export const NavBar = () => {
  const [isOpen, setIsOpen] = useState(false);

  const navItems = [
    { name: 'Categorías', path: '/', icon: Grid },
    { name: 'Ranking', path: '/ranking', icon: Trophy },
    { name: 'Administración', path: '/admin', icon: ShieldPlus }
  ];

  const desktopBaseStyles = "flex items-center gap-2 px-4 py-2 rounded-xl font-bold transition-all";


  return (
    <>
      <nav className="hidden md:flex flex-1 items-center justify-center gap-2 lg:gap-6 mx-4">
        {navItems.map((item) => {
          const Icon = item.icon;
          return (
            <NavLink
              key={item.path}
              to={item.path}
              className={({ isActive }) => twMerge(
                clsx(
                  desktopBaseStyles,
                  isActive ? VARIANTS.ring.navActive : VARIANTS.ring.navInactive
                )
              )}
            >
              <Icon size={18} />
              <span>{item.name}</span>
            </NavLink>
          );
        })}
      </nav>

      <div className="flex md:hidden items-center">
        <Button
          onClick={() => setIsOpen(!isOpen)}
          variant="outline"
          color="neutral"
          size="iconSm"
          radius="lg"
          className="bg-white"
          aria-label={isOpen ? "Cerrar menú" : "Abrir menú"}
          icon={isOpen ? X : Menu}
        />
      </div>

      <NavBarMobile
        isOpen={isOpen}
        setIsOpen={setIsOpen}
        navItems={navItems}
      />
    </>
  );
};