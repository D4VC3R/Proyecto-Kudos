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
    </footer>
  );
};

export default Footer;