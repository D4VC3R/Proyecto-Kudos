import React from 'react';
import { SkipForward, MessageSquare } from 'lucide-react';
import clsx from 'clsx';

export const VoteActions = ({ onSkip, isPending, showComments, onToggleComments }) => {
  return (
    <div className="flex items-center justify-between">
      <button
        onClick={onSkip}
        disabled={isPending}
        className="flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-slate-500 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition-colors disabled:opacity-50"
      >
        <SkipForward size={20} />
        <span>Saltar (Skip)</span>
      </button>
      
      <button
        onClick={onToggleComments}
        className={clsx(
          "flex items-center gap-2 px-6 py-3 rounded-xl font-bold border transition-colors",
          showComments 
            ? "text-blue-700 bg-blue-100 border-blue-300"
            : "text-blue-600 bg-blue-50 hover:bg-blue-100 border-blue-200"
        )}
      >
        <MessageSquare size={20} />
        <span>{showComments ? 'Ocultar comentarios' : 'Comentar antes'}</span>
      </button>
    </div>
  );
};

