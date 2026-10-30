import React from 'react';
import {FolderGit2} from 'lucide-react';

const Footer = () => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="w-full border-t border-border bg-background py-6 shrink-0 mt-auto">
      <div
        className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 sm:px-6 md:flex-row lg:px-8">

        <div className="flex flex-col items-center md:items-start">
          <span className="text-lg font-black text-text-highlight">
            Proyecto Kudos
          </span>
          <span className="text-sm font-medium text-text-normal">
            © {currentYear} · David Cerdán Valero.
          </span>
        </div>

        <div className="flex flex-col items-center text-sm font-medium text-text-normal">
          <span className="font-bold text-text-highlight">
            Desarrollo de Aplicaciones Web
          </span>
          <span>IES Paco Mollà</span>
        </div>

        <a
          href="https://github.com/D4VC3R/Proyecto-Kudos"
          target="_blank"
          rel="noopener noreferrer"
          className="flex items-center gap-2 text-sm font-bold text-nav-item transition-colors hover:text-blue-500"
          title="Ver código en GitHub"
        >
          <FolderGit2 size={20}/>
          <span>GitHub</span>
        </a>

      </div>

      <div
        className="mx-auto mt-6 flex max-w-7xl flex-col items-center gap-3 border-t border-border px-4 pt-6 text-center sm:flex-row sm:justify-center sm:gap-4 sm:px-6 sm:text-left lg:px-8">
        <a
          href="https://www.themoviedb.org"
          target="_blank"
          rel="noopener noreferrer"
          className="shrink-0"
          title="The Movie Database (TMDB)"
        >
          <img src="/tmdb-logo.svg" alt="The Movie Database (TMDB)" width="273" height="36" className="h-4 w-auto"/>
        </a>
        <p className="max-w-2xl text-xs font-medium text-text-normal">
          <span lang="en">This website uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB.</span>
          {' '}
          <span className="block sm:inline">Esta web usa TMDB y sus API, pero TMDB no la respalda, certifica ni aprueba.</span>
        </p>
      </div>
    </footer>
  );
};

export default Footer;