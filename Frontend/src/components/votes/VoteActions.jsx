import React from 'react';
import { SkipForward, MessageSquare } from 'lucide-react';
import { Button } from '../common/Button';

export const VoteActions = ({ onSkip, showComments, onToggleComments }) => {
  return (
    <div className="flex items-center justify-between">
      <Button
        onClick={onSkip}
        variant="outline"
        color="neutral"
        icon={SkipForward}
      >
        No me interesa
      </Button>

      <Button
        onClick={onToggleComments}
        variant="ghost"
        color="primary"
        icon={MessageSquare}
      >
        {showComments ? 'Ocultar comentarios' : 'Comentar'}
      </Button>
    </div>
  );
};
