import React from 'react';
import {Link, useLocation} from 'react-router-dom';
import Button from '../ui/Button.jsx';
import {buildAuthLinkState} from '../../lib/authRedirect.js';

// Cabecera sin sesión: solo "Entrar" y "Regístrate" (F0-24). Los dos guardan la página actual
// para volver a ella tras el login.
const HeaderLogin = () => {
  const location = useLocation();
  const linkState = buildAuthLinkState(location);

  return (
    <div className="flex items-center gap-2">
      {/* Padding algo menor en móvil para que quepan junto al logo y el menú. */}
      <Button as={Link} to="/login" state={linkState} variant="ghost" className="whitespace-nowrap px-3 sm:px-4">
        Entrar
      </Button>
      <Button as={Link} to="/register" state={linkState} variant="primary" className="whitespace-nowrap px-3 sm:px-4">
        Regístrate
      </Button>
    </div>
  );
};

export default HeaderLogin;