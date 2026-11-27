import React, { useCallback, useId, useRef, useState } from 'react';
import { NavLink } from 'react-router-dom';
import { LogOut, Menu, X } from 'lucide-react';
import { twMerge } from '../../lib/twMerge.js';
import GlobalSearch from './GlobalSearch.jsx';
import ThemePicker from './ThemePicker.jsx';
import { FOCUS_RING, ICON_BUTTON, NAV_ACTIVE } from './headerStyles.js';
import { useDismiss } from '../../hooks/common/useDismiss.js';
import { useFocusTrap } from '../../hooks/common/useFocusTrap.js';
import { useLogout } from '../../hooks/auth/useAuthMutations.js';
import { useNavItems } from '../../hooks/common/useNavItems.js';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore.js';

const ROW = 'flex min-h-12 w-full items-center gap-2.5 border-b border-line px-1 text-left text-body font-medium';

// ☰ y panel de navegación para pantallas sin barra de escritorio: búsqueda, enlaces, tema y cierre de sesión.
const NavBarMobile = () => {
  const [isOpen, setIsOpen] = useState(false);
  const items = useNavItems();
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const { mutate: logout, isPending } = useLogout();
  const panelId = useId();
  const containerRef = useRef(null);
  const buttonRef = useRef(null);
  const toggle = useCallback(() => setIsOpen((open) => !open), []);
  const close = useCallback(() => setIsOpen(false), []);
  // El contenedor abarca el botón y el panel: así pulsar ☰ no cuenta como "clic fuera".
  useDismiss({ isOpen, containerRef, onDismiss: close, returnFocusRef: buttonRef });
  useFocusTrap({ isOpen, containerRef });

  return (
    <div ref={containerRef} className="flex lg:hidden">
      <button
        ref={buttonRef}
        type="button"
        onClick={toggle}
        aria-expanded={isOpen}
        aria-controls={panelId}
        aria-label={isOpen ? 'Cerrar menú' : 'Abrir menú'}
        className={ICON_BUTTON}
      >
        {isOpen ? <X size={20} aria-hidden="true" /> : <Menu size={20} aria-hidden="true" />}
      </button>

      {isOpen && (
        <div
          id={panelId}
          className="absolute inset-x-0 top-full z-40 grid max-h-below-header gap-1 overflow-y-auto border-b border-line bg-surface px-4 pb-5 pt-4"
        >
          <GlobalSearch onNavigate={close} />
          <nav aria-label="Principal (móvil)" className="grid">
            {items.map((item) => {
              const Icon = item.icon;
              return (
                <NavLink
                  key={item.path}
                  to={item.path}
                  onClick={close}
                  className={({ isActive }) => twMerge(ROW, isActive ? NAV_ACTIVE : 'text-ink', FOCUS_RING)}
                >
                  <Icon size={20} aria-hidden="true" />
                  <span>{item.name}</span>
                </NavLink>
              );
            })}
          </nav>
          <ThemePicker className="pt-3" />
          {isAuthenticated && (
            <button
              type="button"
              onClick={() => logout()}
              disabled={isPending}
              className={twMerge(ROW, 'border-b-0 text-ink', FOCUS_RING)}
            >
              <LogOut size={20} aria-hidden="true" />
              <span>Cerrar sesión</span>
            </button>
          )}
        </div>
      )}
    </div>
  );
};

export default NavBarMobile;
