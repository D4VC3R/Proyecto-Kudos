import React from 'react';

// Aviso de que el texto legal es un borrador no aprobado (F3-07). Se retira cuando el titular lo apruebe.
const LegalDraftNotice = () => (
  <p role="note" className="mb-8 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-medium text-amber-900">
    <strong>Borrador pendiente de revisión.</strong> Este texto es un borrador y todavía no lo ha aprobado el titular.
    Los datos marcados como «Pendiente» faltan por completar.
  </p>
);

export default LegalDraftNotice;
