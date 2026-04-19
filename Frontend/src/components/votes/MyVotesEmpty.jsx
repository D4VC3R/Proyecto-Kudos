import React from 'react';
import { ThumbsUp } from 'lucide-react';
import { Link } from 'react-router-dom';
import { FeedbackState } from '../common/FeedbackState';

export const MyVotesEmpty = () => {
  return (
    <FeedbackState
      isAnimated
      icon={ThumbsUp}
      iconColorClass="h-20 w-20 rounded-3xl bg-blue-50 text-blue-500"
      title="No tienes votos registrados"
      description="Aún no has valorado ningún ítem."
      customLayoutClass="border border-slate-200 shadow-sm bg-white rounded-3xl"
    >
      <Link
        to="/"
        className="inline-flex -translate-y-1 items-center gap-2 rounded-2xl bg-blue-600 px-6 py-3 font-bold text-white shadow-lg transition-transform hover:-translate-y-2 active:translate-y-0"
      >
        <ThumbsUp size={18} />
        Comenzar a votar
      </Link>
    </FeedbackState>
  );
};
