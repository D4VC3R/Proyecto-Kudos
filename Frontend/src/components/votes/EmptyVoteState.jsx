import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Star } from 'lucide-react';

export const EmptyVoteState = () => {
  const navigate = useNavigate();

  return (
    <div className="mx-auto mt-20 max-w-2xl text-center flex flex-col items-center">
      <div className="w-24 h-24 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center mb-6">
        <Star size={48} />
      </div>
      <h2 className="text-3xl font-black text-slate-900 mb-2">¡Todo al día!</h2>
      <p className="text-lg text-slate-500 mb-8">No hay más ítems pendientes de votar en esta categoría, has ganado muchos Kudos.</p>
      <button
        onClick={() => navigate(`/`)}
        className="px-6 py-3 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition-colors"
      >
        Seleccionar nueva categoría
      </button>
    </div>
  );
};

