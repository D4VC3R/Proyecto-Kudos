import React from 'react';
import SectionHead from '../ui/SectionHead.jsx';

// Sección de la portada (F2-09): título accesible y contenido.
const HomeSection = ({ id, title, children }) => (
  <section aria-labelledby={id} className="flex flex-col gap-4">
    <SectionHead id={id} title={title} />
    {children}
  </section>
);

export default HomeSection;
