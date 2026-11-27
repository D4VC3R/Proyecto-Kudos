import React, { useCallback, useEffect, useId, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { LogOut, ShieldPlus, User, UserCircle } from 'lucide-react';
import KudosBadge from '../ui/KudosBadge.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import ThemePicker from './ThemePicker.jsx';
import { FOCUS_RING, MENU_ITEM } from './headerStyles.js';
import { useDismiss } from '../../hooks/common/useDismiss.js';
import { useLogout } from '../../hooks/auth/useAuthMutations.js';
import { useMinimalProfile } from '../../hooks/users/useUserQueries.js';
import { selectIsAdmin, useSessionStore } from '../../store/useSessionStore.js';

const ITEMS = '[role^="menuitem"]:not([disabled])';

// Píldora de la cabecera (nombre, kudos y posición) y su menú: perfil, administración, tema y cierre de sesión.
// Es un botón de menú: se maneja con el teclado como tal (flechas, Inicio, Fin, Escape).
const UserMenu = () => {
  const user = useSessionStore((state) => state.user);
  const isAdmin = useSessionStore(selectIsAdmin);
  const { data: profile } = useMinimalProfile();
  const { mutate: logout, isPending } = useLogout();
  const [isOpen, setIsOpen] = useState(false);
  const panelId = useId();
  const containerRef = useRef(null);
  const buttonRef = useRef(null);
  const menuRef = useRef(null);
  // Qué elemento recibe el foco al abrir: el primero (clic, Intro, Espacio y ↓) o el último (↑).
  const initialFocus = useRef('first');
  const close = useCallback(() => setIsOpen(false), []);
  // El contenedor abarca la píldora y el panel: así pulsar la píldora no cuenta como "clic fuera".
  useDismiss({ isOpen, containerRef, onDismiss: close, returnFocusRef: buttonRef });

  useEffect(() => {
    if (!isOpen) return;
    const items = menuRef.current?.querySelectorAll(ITEMS);
    if (!items?.length) return;
    items[initialFocus.current === 'last' ? items.length - 1 : 0].focus();
  }, [isOpen]);

  const name = user?.name || 'Mi cuenta';
  const kudos = user?.total_kudos ?? 0;
  const rank = profile?.ranking_position || null;

  const open = (which) => {
    initialFocus.current = which;
    setIsOpen(true);
  };

  const handleButtonClick = () => {
    if (isOpen) close();
    else open('first');
  };

  const handleButtonKeyDown = (e) => {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      open(e.key === 'ArrowUp' ? 'last' : 'first');
    }
  };

  const handleMenuKeyDown = (e) => {
    const items = [...menuRef.current.querySelectorAll(ITEMS)];
    const index = items.indexOf(document.activeElement);

    if (e.key === 'Tab') {
      // El panel se desmonta: el foco pasa antes a la píldora para que el Tab siga desde ella.
      close();
      buttonRef.current?.focus();
      return;
    }
    // Un enlace solo responde a Intro; en un menú también se activa con Espacio.
    if (e.key === ' ' && document.activeElement?.tagName === 'A') {
      e.preventDefault();
      document.activeElement.click();
      return;
    }

    let target;
    if (e.key === 'ArrowDown') target = (index + 1) % items.length;
    else if (e.key === 'ArrowUp') target = (index - 1 + items.length) % items.length;
    else if (e.key === 'Home') target = 0;
    else if (e.key === 'End') target = items.length - 1;
    else return;

    e.preventDefault();
    items[target]?.focus();
  };

  return (
    <div ref={containerRef} className="relative">
      <button
        ref={buttonRef}
        type="button"
        onClick={handleButtonClick}
        onKeyDown={handleButtonKeyDown}
        aria-haspopup="menu"
        aria-expanded={isOpen}
        aria-controls={panelId}
        aria-label={`${name}: ${kudos} kudos, posición ${rank ?? 'sin posición'}. Abrir menú de usuario`}
        className={`inline-flex h-10 items-center gap-2 rounded-full border border-line bg-surface pl-1.5 pr-2 hover:border-line-strong ${FOCUS_RING}`}
      >
        <span className="hidden h-7 w-7 shrink-0 place-items-center overflow-hidden rounded-full md:grid">
          <StorageImage src={profile?.avatar} alt="" className="h-full w-full" fallbackIcon={UserCircle} />
        </span>
        <span className="hidden max-w-40 truncate text-small font-semibold text-ink xl:inline">{name}</span>
        <KudosBadge value={kudos} />
        <span className="text-caption font-semibold tabular-nums text-ink-muted">#{rank ?? '-'}</span>
      </button>

      {isOpen && (
        <div
          id={panelId}
          className="absolute right-0 top-full z-50 mt-2 w-64 rounded-md border border-line bg-surface p-1.5 shadow-raised"
        >
          <div className="grid gap-1.5 p-2.5">
            <p className="truncate text-small font-semibold text-ink">{name}</p>
            <div className="flex items-center gap-2">
              <KudosBadge value={kudos} />
              <span className="text-caption text-ink-muted">
                {rank ? `#${rank} del ranking` : 'Sin posición en el ranking'}
              </span>
            </div>
          </div>

          <div ref={menuRef} role="menu" aria-label="Menú de usuario" onKeyDown={handleMenuKeyDown} className="grid gap-0.5">
            <Link to="/profile" role="menuitem" tabIndex={-1} onClick={close} className={MENU_ITEM}>
              <User size={18} aria-hidden="true" />
              Mi perfil
            </Link>
            {isAdmin && (
              <Link to="/admin" role="menuitem" tabIndex={-1} onClick={close} className={MENU_ITEM}>
                <ShieldPlus size={18} aria-hidden="true" />
                Administración
              </Link>
            )}
            <div role="separator" className="my-1.5 border-t border-line" />
            <ThemePicker variant="menu" />
            <div role="separator" className="my-1.5 border-t border-line" />
            <button
              type="button"
              role="menuitem"
              tabIndex={-1}
              onClick={() => logout()}
              disabled={isPending}
              className={MENU_ITEM}
            >
              <LogOut size={18} aria-hidden="true" />
              Cerrar sesión
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default UserMenu;
