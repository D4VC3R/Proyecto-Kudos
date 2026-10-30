import React from 'react';
import { MessageSquare } from 'lucide-react';
import Button from '../ui/Button.jsx';

const VoteActionButtons = ({ showComments, onToggleComments }) => {
  return (
    <div className="flex items-center justify-end">
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

export default VoteActionButtons;
