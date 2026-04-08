import React from 'react';
import { useNextCategoryItem } from './../hooks/categories/useCategoryQueries';
import { useCreateVote } from './../hooks/votes/useVoteMutations';

const ItemTinderView = ({ categoryId }) => {
  // Pedimos el siguiente ítem
  const { data: result, isLoading } = useNextCategoryItem(categoryId);

  // Preparamos la mutación
  const { mutate: emitirVoto, isPending } = useCreateVote();

  if (isLoading) return <p>Cargando siguiente ítem...</p>;
  if (!result?.data) return <p>¡Has completado esta categoría! No hay más ítems.</p>;

  const item = result.data;

  const handleVote = (score) => {
    emitirVoto({ item_id: item.id, score, type: 'vote' });
  };

  const handleSkip = () => {
    emitirVoto({ item_id: item.id, type: 'skip' });
  };

  return (
    <div className="tinder-card">
      <h2>{item.name}</h2>
      <p>Kudos en juego... ¡Vota ahora!</p>

      <div className="actions">
        <button disabled={isPending} onClick={handleSkip}>Pasar (Skip)</button>
        <button disabled={isPending} onClick={() => handleVote(1)}>1 Estrella</button>
        <button disabled={isPending} onClick={() => handleVote(5)}>5 Estrellas</button>
      </div>
    </div>
  );
};