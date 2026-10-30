import React from 'react';
import { hasText } from '../../lib/text.js';
import { hasAttribution } from '../../lib/attribution.js';

const linkClassName = 'font-semibold text-slate-700 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-primary';

// Enlace externo de una fuente; si la API no da URL, solo el nombre.
const SourceLink = ({ href, children }) => (
  hasText(href) ? (
    <a href={href} target="_blank" rel="noopener noreferrer" className={linkClassName}>
      {children}
    </a>
  ) : (
    <span className="font-semibold text-slate-700">{children}</span>
  )
);

// Línea de atribución de la ficha: "Datos: TMDB · Descripción: Wikipedia, licencia CC BY-SA 4.0" (legal/cumplimiento §1).
const ItemAttribution = ({ attribution, className = '' }) => {
  if (!hasAttribution(attribution)) return null;

  const { data_source: dataSource, description_source: descriptionSource } = attribution;

  return (
    <p className={`text-xs font-medium leading-relaxed text-slate-500 ${className}`}>
      {dataSource && (
        <span>
          Datos: <SourceLink href={dataSource.url}>{dataSource.name}</SourceLink>
        </span>
      )}
      {dataSource && descriptionSource && <span aria-hidden="true"> · </span>}
      {descriptionSource && (
        <span>
          Descripción: <SourceLink href={descriptionSource.url}>{descriptionSource.name}</SourceLink>
          {hasText(descriptionSource.license) && (
            <>
              , licencia <SourceLink href={descriptionSource.license_url}>{descriptionSource.license}</SourceLink>
            </>
          )}
        </span>
      )}
    </p>
  );
};

export default ItemAttribution;
