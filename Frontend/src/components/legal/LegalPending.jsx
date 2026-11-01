import React from 'react';

// Marca visible de un dato que solo puede aportar el titular (F3-07). Buscable con `grep -rn LegalPending`.
const LegalPending = ({ children }) => (
  <mark className="rounded bg-amber-100 px-1 font-bold text-amber-900">[Pendiente: {children}]</mark>
);

export default LegalPending;
