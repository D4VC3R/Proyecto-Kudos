import React from 'react';
import HomeSection from './HomeSection.jsx';
import HomeItemRow from './HomeItemRow.jsx';
import RowArrows from './RowArrows.jsx';
import { useScrollRow } from '../../hooks/common/useScrollRow.js';

// Sección de la portada con una sola fila (novedades, mejor valorados y populares), con sus flechas junto al título.
const HomeRowSection = ({ id, title, items }) => {
  // `ref` no puede ir en el spread de las flechas: React lo trataría como el ref del componente.
  const { ref, ...arrows } = useScrollRow(items?.length ?? 0);

  return (
    <HomeSection id={id} title={title} tools={<RowArrows controls={`${id}-row`} {...arrows} />}>
      <HomeItemRow id={`${id}-row`} labelledBy={id} rowRef={ref} items={items} />
    </HomeSection>
  );
};

export default HomeRowSection;
