import React from 'react';
import { ThumbsUp } from 'lucide-react';
import { Link } from 'react-router-dom';

import  FeedbackState  from '../ui/FeedbackState.jsx';
import FadeUp from "../animations/FadeUp.jsx";
import Button from "../ui/Button.jsx";

const MyVotesEmpty = () => {
  return (
      <FadeUp>
        <FeedbackState
            icon={ThumbsUp}
            iconColorClass="h-20 w-20 rounded-3xl bg-blue-50 text-blue-500"
            title="No tienes votos registrados"
            description="Aún no has valorado ningún ítem."
            customLayoutClass="border border-border shadow-sm bg-surface rounded-3xl p-12"
        >
          <Button as={Link} to="/" variant="primary" size="lg">
            Comenzar a votar
          </Button>
        </FeedbackState>
      </FadeUp>
  );
};

export default MyVotesEmpty;
