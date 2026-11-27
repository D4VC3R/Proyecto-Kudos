import React from 'react';
import { FolderGit2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import LogoMark from './LogoMark.jsx';
import ThemePicker from './ThemePicker.jsx';
import { FOCUS_RING } from './headerStyles.js';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore.js';

const LINK = `rounded-sm underline underline-offset-4 hover:text-ink ${FOCUS_RING}`;

const Footer = () => {
  const currentYear = new Date().getFullYear();
  const isAuthenticated = useSessionStore(selectIsAuthenticated);

  return (
    <footer className="mt-auto shrink-0 border-t border-line">
      <div className="mx-auto w-full max-w-content px-4 md:px-6">
        <div className="grid gap-5 pb-6 pt-8 md:grid-cols-3">
          <div>
            <p className="mb-2 flex items-center gap-2 font-display text-2xl font-semibold leading-none text-ink">
              <LogoMark size={24} />
              Kudos
            </p>
            <p className="text-small text-ink-muted">© {currentYear} · David Cerdán Valero.</p>
            <nav aria-label="Información legal" className="mt-2 flex gap-3.5 text-small text-ink-muted">
              <Link to="/privacidad" className={LINK}>Privacidad</Link>
              <Link to="/aviso-legal" className={LINK}>Aviso legal</Link>
            </nav>
          </div>

          <div className="flex flex-col text-small text-ink-muted md:text-center">
            <span className="font-semibold text-ink">Desarrollo de Aplicaciones Web</span>
            <span>IES Paco Mollà</span>
          </div>

          <a
            href="https://github.com/D4VC3R/Proyecto-Kudos"
            target="_blank"
            rel="noopener noreferrer"
            className={`inline-flex items-center gap-2 self-start rounded-sm text-small font-semibold text-ink hover:underline md:justify-self-end ${FOCUS_RING}`}
            title="Ver código en GitHub"
          >
            <FolderGit2 size={20} aria-hidden="true" />
            <span>GitHub</span>
          </a>
        </div>

        {!isAuthenticated && <ThemePicker className="border-t border-line py-5" />}

        <div className="grid gap-2.5 border-t border-line pb-8 pt-5 text-caption text-ink-muted">
          <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-4">
            <a
              href="https://www.themoviedb.org"
              target="_blank"
              rel="noopener noreferrer"
              className={`shrink-0 self-start rounded-sm ${FOCUS_RING}`}
              title="The Movie Database (TMDB)"
            >
              <img src="/tmdb-logo.svg" alt="The Movie Database (TMDB)" width="273" height="36" className="h-4 w-auto" />
            </a>
            <p className="max-w-2xl">
              <span lang="en">This website uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB.</span>
              {' '}
              <span className="block sm:inline">Esta web usa TMDB y sus API, pero TMDB no la respalda, certifica ni aprueba.</span>
            </p>
          </div>
          <p>
            Datos de videojuegos de{' '}
            <a href="https://www.igdb.com" target="_blank" rel="noopener noreferrer" className={`font-semibold ${LINK}`}>
              IGDB.com
            </a>.
          </p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
