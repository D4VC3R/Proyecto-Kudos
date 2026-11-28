import React from 'react';
import { Link } from 'react-router-dom';
import { MessageSquare } from 'lucide-react';
import Button from '../ui/Button.jsx';

const VoteActionButtons = ({ itemUrl, showComments, onToggleComments, commentsId }) => {
  return (
    <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2">
      <Button as={Link} to={itemUrl} variant="link">
        Ver ficha completa
      </Button>
      <Button
        variant="link"
        icon={MessageSquare}
        aria-expanded={showComments}
        aria-controls={commentsId}
        onClick={onToggleComments}
      >
        {showComments ? 'Ocultar comentarios' : 'Comentar'}
      </Button>
    </div>
  );
};

export default VoteActionButtons;
