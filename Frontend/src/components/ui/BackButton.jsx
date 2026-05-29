import React from 'react';
import {ArrowLeft} from 'lucide-react';
import {useNavigate} from 'react-router-dom';
import Button from './Button.jsx';

const BackButton = ({to = -1, label = 'Volver', className = ''}) => {
  const navigate = useNavigate();

  return (
    <Button
      type="button"
      variant="ghost"
      color="neutral"
      radius="full"
      icon={ArrowLeft}
      className={`w-fit self-start ${className}`}
      onClick={() => navigate(to)}
    >
      {label}
    </Button>
  );
};

export default BackButton;
