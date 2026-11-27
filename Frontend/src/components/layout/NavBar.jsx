import React, { useCallback, useId, useRef, useState } from 'react';
import { NavLink } from 'react-router-dom';
import { Grid, Trophy, Menu, X, ShieldPlus, Bookmark, ListPlus } from 'lucide-react';
import { twMerge } from 'tailwind-merge';
import clsx from 'clsx';
import Button from '../ui/Button.jsx';
import { VARIANTS } from "../../lib/buttonStyles.js";
import NavBarMobile from "./NavBarMobile.jsx";
import { useDismiss } from "../../hooks/common/useDismiss.js";
import { selectIsAdmin, selectIsAuthenticated, selectIsVerified, useSessionStore } from "../../store/useSessionStore.js";

const NavBar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const isAdmin = useSessionStore(selectIsAdmin);
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const isVerified = useSessionStore(selectIsVerified);
  const panelId = useId();
  const containerRef = useRef(null);
  const buttonRef = useRef(null);
  const toggle = useCallback(() => setIsOpen((open) => !open), []);
  const close = useCallback(() => setIsOpen(false), []);
  // El contenedor abarca el botón y el panel: así pulsar ☰ no cuenta como "clic fuera".
  useDismiss({ isOpen, containerRef, onDismiss: close, returnFocusRef: buttonRef });

  const navItems = [
    { name: 'Categorías', path: '/', icon: Grid },
    { name: 'Ranking', path: '/ranking', icon: Trophy },
    { name: 'Wishlist', path: '/wishlist', icon: Bookmark, requiresVerified: true },
    { name: 'Mis listas', path: '/mis-listas', icon: ListPlus, requiresVerified: true },
    { name: 'Administración', path: '/admin', icon: ShieldPlus, requiresAdmin: true }
  ];

  const visibleNavItems = navItems.filter(
    (item) => (!item.requiresAdmin || isAdmin) && (!item.requiresVerified || (isAuthenticated && isVerified))
  );
  // Con cinco enlaces (admin) los nombres no caben junto al buscador ni a 1280 px (la cabecera es max-w-7xl):
  // solo icono a cualquier ancho. Con cuatro o menos, el nombre se ve desde xl.
  const showLabels = visibleNavItems.length <= 4;
  const desktopBaseStyles = "flex items-center gap-2 px-4 py-2 rounded-xl font-bold transition-all whitespace-nowrap";


  return (
    <>
      <nav className="hidden md:flex flex-1 items-center justify-center gap-2 2xl:gap-6 mx-4">
        {visibleNavItems.map((item) => {
          const Icon = item.icon;
          return (
            <NavLink
              key={item.path}
              to={item.path}
              // Por debajo de xl, o con cinco enlaces (admin), solo se ve el icono: el nombre sigue en aria-label y title.
              aria-label={item.name}
              title={item.name}
              className={({ isActive }) => twMerge(
                clsx(
                  desktopBaseStyles,
                  isActive ? VARIANTS.ring.navActive : VARIANTS.ring.navInactive
                )
              )}
            >
              <Icon size={18} className="shrink-0" />
              <span className={clsx('hidden', showLabels && 'xl:inline')}>{item.name}</span>
            </NavLink>
          );
        })}
      </nav>

      <div ref={containerRef} className="flex md:hidden items-center">
        <Button
          ref={buttonRef}
          onClick={toggle}
          aria-expanded={isOpen}
          aria-controls={panelId}
          variant="outline"
          color="neutral"
          size="iconSm"
          radius="lg"
          className="bg-surface"
          aria-label={isOpen ? "Cerrar menú" : "Abrir menú"}
          icon={isOpen ? X : Menu}
        />
        <NavBarMobile
          id={panelId}
          isOpen={isOpen}
          onClose={close}
          navItems={visibleNavItems}
        />
      </div>
    </>
  );
};

export default NavBar;