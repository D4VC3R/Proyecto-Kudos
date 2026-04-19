import React from 'react';
import { SectionHeader } from '../common/SectionHeader';

export const LoginHeader = () => {
  return (
    <header className="space-y-1">
      <SectionHeader
        title="Iniciar sesion"
        subtitle="Accede con tu cuenta para continuar en la plataforma Kudos."
      />
    </header>
  );
};
