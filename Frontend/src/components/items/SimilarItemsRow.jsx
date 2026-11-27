import React from 'react';
import HomeRowSection from '../home/HomeRowSection.jsx';

// Fila "Similares" de la ficha, con la misma fila desplazable y flechas de la portada. Sin ítems no pinta nada.
const SimilarItemsRow = ({ items = [] }) => {
  if (!items?.length) return null;

  return <HomeRowSection id="similar-items" title="Similares" items={items} />;
};

export default SimilarItemsRow;
