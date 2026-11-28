import React from 'react';

// Marca visible de un dato que solo puede aportar el titular. Buscable con `grep -rn LegalPending`.
// Fondo translúcido sobre `canvas`: se lee igual en todos los temas.
const LegalPending = ({ children }) => (
  <mark className="rounded-sm bg-warning/15 px-1 font-semibold text-ink ring-1 ring-inset ring-warning/40">[Pendiente: {children}]</mark>
);

export default LegalPending;
