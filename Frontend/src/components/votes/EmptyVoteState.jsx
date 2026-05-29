import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Star } from 'lucide-react';
import  Button from '../ui/Button.jsx';
import AnimatedItem from "../animations/AnimatedItem.jsx";

const EmptyVoteState = () => {
  const navigate = useNavigate();

  return (
    <AnimatedItem>
    <div className="mx-auto mt-20 max-w-2xl text-center flex flex-col items-center">
      <div className="w-24 h-24 bg-yellow-100 text-accent rounded-full flex items-center justify-center mb-6">
        <Star size={48} />
      </div>
      <h2 className="text-3xl font-black text-text-highlight mb-2">¡Todo al día!</h2>
      <p className="text-lg text-text-normal mb-8">No tenemos nada más que ofrecerte aquí, ¿Por qué no exploras otra categoría?.</p>
      <Button
        onClick={() => navigate(`/`)}
        variant="solid"
        color="primary"
        radius="xl"
      >
        Seleccionar nueva categoría
      </Button>
    </div>
    </AnimatedItem>
  );
};

export default EmptyVoteState;
