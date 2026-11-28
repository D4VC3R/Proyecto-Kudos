import React from 'react';
import PageHeader from '../ui/PageHeader.jsx';

// Estructura común de las páginas legales: el `h1` de PageHeader y el texto a 65 caracteres por línea; el ancho
// de la página lo pone App.jsx. Cada sección del contenido usa <section> con <h2>.
const LegalPageLayout = ({ title, children }) => (
  <article className="flex w-full flex-col gap-8">
    <PageHeader title={title} />
    <div className="flex max-w-prose flex-col gap-8 text-body leading-relaxed text-ink">
      {children}
    </div>
  </article>
);

export default LegalPageLayout;
