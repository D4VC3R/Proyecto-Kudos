import React from 'react';
import LegalDraftNotice from './LegalDraftNotice.jsx';

// Estructura común de las páginas legales (F3-07). Cada sección del contenido usa <section> con <h2>.
const LegalPageLayout = ({ title, children }) => (
  <article className="mx-auto my-8 w-full max-w-3xl rounded-3xl bg-surface p-6 shadow-xl ring-1 ring-slate-200 sm:p-10">
    <h1 className="mb-6 text-3xl font-black tracking-tight text-text-highlight">{title}</h1>
    <LegalDraftNotice/>
    <div className="flex flex-col gap-6 text-text-normal">
      {children}
    </div>
  </article>
);

export default LegalPageLayout;
