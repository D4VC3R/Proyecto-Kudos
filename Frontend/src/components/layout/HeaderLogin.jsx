import React from 'react';
import {Link, useLocation} from 'react-router-dom';
import clsx from 'clsx';
import {VARIANTS, RADII} from '../../lib/buttonStyles.js';
import {buildAuthLinkState} from '../../lib/authRedirect.js';

// Enlaces con aspecto de botón (los estilos base de Button no se exportan y Button siempre es un <button>).
// Padding algo menor en móvil para que quepan junto al logo y el menú.
const linkBaseStyles = 'inline-flex items-center justify-center whitespace-nowrap font-bold text-sm py-1.5 px-3 sm:px-4 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200';

// Cabecera sin sesión: solo "Entrar" y "Regístrate" (F0-24). Los dos guardan la página actual
// para volver a ella tras el login.
const HeaderLogin = () => {
  const location = useLocation();
  const linkState = buildAuthLinkState(location);

  return (
    <div className="flex items-center gap-2">
      <Link to="/login" state={linkState} className={clsx(linkBaseStyles, VARIANTS.ghost.neutral, RADII.lg)}>
        Entrar
      </Link>
      <Link to="/register" state={linkState} className={clsx(linkBaseStyles, VARIANTS.solid.primary, RADII.lg)}>
        Regístrate
      </Link>
    </div>
  );
};

export default HeaderLogin;