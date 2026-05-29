import React from 'react';
import { ThumbsUp } from 'lucide-react';
import { Link } from 'react-router-dom';

import  FeedbackState  from '../ui/FeedbackState.jsx';
import ScaleFadeIn from "../animations/ScaleFadeIn.jsx";

const MyVotesEmpty = () => {
  return (
      <ScaleFadeIn>
        <FeedbackState
            icon={ThumbsUp}
            iconColorClass="h-20 w-20 rounded-3xl bg-blue-50 text-blue-500"
            title="No tienes votos registrados"
            description="Aún no has valorado ningún ítem."
            customLayoutClass="border border-border shadow-sm bg-surface rounded-3xl p-12"
        >
          <Link
              to="/"
              className="inline-flex items-center gap-2 rounded-2xl bg-primary px-6 py-3 font-bold text-text-btn shadow-lg transition-transform hover:-translate-y-1 active:translate-y-0"
          >
            Comenzar a votar
          </Link>
        </FeedbackState>
      </ScaleFadeIn>
  );
};

export default MyVotesEmpty;
