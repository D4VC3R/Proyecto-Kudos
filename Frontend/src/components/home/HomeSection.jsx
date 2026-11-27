import React from 'react';
import SectionHead from '../ui/SectionHead.jsx';

// Sección de la portada: título accesible, herramientas opcionales junto a él (las flechas de su fila) y contenido.
const HomeSection = ({ id, title, tools, children }) => (
  <section aria-labelledby={id} className="flex flex-col gap-4">
    <SectionHead id={id} title={title}>{tools}</SectionHead>
    {children}
  </section>
);

export default HomeSection;
