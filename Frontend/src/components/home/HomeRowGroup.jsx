import React from 'react';
import HomeItemRow from './HomeItemRow.jsx';
import RowArrows from './RowArrows.jsx';
import { useScrollRow } from '../../hooks/common/useScrollRow.js';

// Una fila de "Para ti": con `because` lleva su título "Porque te gustó X" y sus flechas; sin él, solo las flechas.
const HomeRowGroup = ({ id, because, items, labelledBy }) => {
  // `ref` no puede ir en el spread de las flechas: React lo trataría como el ref del componente.
  const { ref, ...arrows } = useScrollRow(items?.length ?? 0);
  if (!items?.length) return null;

  return (
    <div className="flex flex-col gap-3">
      <div className={`flex items-center gap-3 ${because ? 'justify-between' : 'justify-end'}`}>
        {because && (
          <h3 id={`${id}-title`} className="text-h3 text-ink">
            Porque te gustó <span className="font-display text-lg">{because}</span>
          </h3>
        )}
        <RowArrows controls={`${id}-row`} {...arrows} />
      </div>
      <HomeItemRow id={`${id}-row`} labelledBy={because ? `${id}-title` : labelledBy} rowRef={ref} items={items} />
    </div>
  );
};

export default HomeRowGroup;
