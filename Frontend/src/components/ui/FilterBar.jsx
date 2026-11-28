import React from 'react';

// Barra de filtros de un listado: los campos van con la etiqueta visible.
const FilterBar = ({ label, children }) => (
  <div role="search" aria-label={label} className="flex flex-wrap items-end gap-3 rounded-md border border-line bg-surface p-3">
    {children}
  </div>
);

export default FilterBar;
