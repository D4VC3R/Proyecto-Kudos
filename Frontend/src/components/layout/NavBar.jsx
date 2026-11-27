import React from 'react';
import { NavLink } from 'react-router-dom';
import { twMerge } from '../../lib/twMerge.js';
import { useNavItems } from '../../hooks/common/useNavItems.js';
import { FOCUS_RING, NAV_ACTIVE } from './headerStyles.js';

// Navegación de escritorio. «Administración» no está aquí: va en el menú de usuario para que los enlaces quepan con texto.
const NavBar = () => {
  const items = useNavItems().filter((item) => !item.requiresAdmin);

  return (
    <nav aria-label="Principal" className="ml-5 hidden items-center gap-0.5 lg:flex">
      {items.map((item) => (
        <NavLink
          key={item.path}
          to={item.path}
          className={({ isActive }) => twMerge(
            'rounded-sm px-2.5 py-2 text-small font-medium whitespace-nowrap',
            isActive ? NAV_ACTIVE : 'text-ink-muted hover:text-ink',
            FOCUS_RING,
          )}
        >
          {item.name}
        </NavLink>
      ))}
    </nav>
  );
};

export default NavBar;
