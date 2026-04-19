import React from 'react';
import { FilePlus } from 'lucide-react';
import { FeedbackState } from '../common/FeedbackState';

export const NewProposalHeader = ({ categoryName }) => {
  return (
    <FeedbackState
      icon={FilePlus}
      iconColorClass="bg-red-100 text-red-600 shadow-inner rounded-2xl h-16 w-16"
      title={
        <>
          Crear <span className="text-red-500 drop-shadow-sm">Nueva Propuesta</span>
        </>
      }
      description={`Ayuda a expandir el universo de ${categoryName} proponiendo nuevos ítems. Un embajador revisará tu idea para validarla.`}
      customLayoutClass="mb-10 text-center"
    />
  );
};
