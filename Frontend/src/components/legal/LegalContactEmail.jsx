import React from 'react';
import LegalPending from './LegalPending.jsx';

// Correo de contacto legal (LEGAL_CONTACT_EMAIL, vía GET /api/legal). Si no está configurado, muestra el marcador.
const LegalContactEmail = ({ email, isLoading }) => {
  if (isLoading) return <span>…</span>;

  if (email) {
    return <a href={`mailto:${email}`} className="font-bold text-primary hover:underline">{email}</a>;
  }

  return <LegalPending>correo de contacto legal (LEGAL_CONTACT_EMAIL)</LegalPending>;
};

export default LegalContactEmail;
