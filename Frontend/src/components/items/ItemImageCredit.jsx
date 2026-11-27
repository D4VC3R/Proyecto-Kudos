import React from 'react';
import { getCommonsImageCredit } from '../../lib/attribution.js';
import { SourceLink } from './ItemAttribution.jsx';

// Pie de la foto: "Foto: {autor}, {licencia}, vía Wikimedia Commons" (legal/cumplimiento §1, F4-04).
// La licencia enlaza a su texto (si se conoce) y "Wikimedia Commons" a la página del archivo, que tiene todos los datos.
const ItemImageCredit = ({ image, className = '' }) => {
  const credit = getCommonsImageCredit(image);
  if (!credit) return null;

  return (
    <p className={`max-w-2xl break-words text-xs font-medium leading-relaxed text-ink-muted ${className}`}>
      Foto: <span className="font-semibold text-ink">{credit.author}</span>,{' '}
      <SourceLink href={credit.licenseUrl}>{credit.license}</SourceLink>, vía{' '}
      <SourceLink href={credit.sourceUrl}>Wikimedia Commons</SourceLink>
    </p>
  );
};

export default ItemImageCredit;
