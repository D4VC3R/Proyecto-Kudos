import React from 'react';

// Sección de la portada (F2-09): título accesible y contenido.
const HomeSection = ({ id, title, children }) => (
  <section aria-labelledby={id} className="flex flex-col gap-4">
    <h2 id={id} className="text-2xl font-black tracking-tight text-text-highlight">{title}</h2>
    {children}
  </section>
);

export default HomeSection;
