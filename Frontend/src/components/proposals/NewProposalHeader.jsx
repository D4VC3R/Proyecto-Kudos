import React from 'react';
import PageHeader from '../ui/PageHeader.jsx';

const NewProposalHeader = ({ categoryName }) => {
  return (
    <div className="mb-8">
      <PageHeader
        kicker={categoryName}
        title="Crear"
        highlight="Nueva Propuesta"
        subtitle={`Ayuda a expandir el universo de ${categoryName} proponiendo nuevos ítems. Un embajador revisará tu idea para validarla.`}
      />
    </div>
  );
};

export default NewProposalHeader;
