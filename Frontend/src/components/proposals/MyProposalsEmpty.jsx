import React from 'react';
import { FileText } from 'lucide-react';
import FeedbackState from '../ui/FeedbackState.jsx';

const MyProposalsEmpty = () => {
  return (
    <FeedbackState
      icon={FileText}
      titleAs="h3"
      title="No tienes propuestas"
      description="Aún no has sugerido ningún ítem. Dirígete a alguna categoría y crea tu primera propuesta para ganar Kudos."
      customLayoutClass="rounded-md border border-line bg-surface p-12"
    />
  );
};

export default MyProposalsEmpty;
