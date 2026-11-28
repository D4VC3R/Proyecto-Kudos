import React from 'react';
import { ThumbsUp } from 'lucide-react';
import { Link } from 'react-router-dom';

import  FeedbackState  from '../ui/FeedbackState.jsx';
import Button from "../ui/Button.jsx";

const MyVotesEmpty = () => {
  return (
    <FeedbackState
        icon={ThumbsUp}
        titleAs="h3"
        title="No tienes votos registrados"
        description="Aún no has valorado ningún ítem."
        customLayoutClass="rounded-md border border-line bg-surface p-12"
    >
      <Button as={Link} to="/" variant="primary" size="lg">
        Comenzar a votar
      </Button>
    </FeedbackState>
  );
};

export default MyVotesEmpty;
