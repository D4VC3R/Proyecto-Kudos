import { useParams } from 'react-router-dom';
import { StateCard } from '../components/common/StateCard';

export const VotePage = () => {
  const { categoryId } = useParams();

  return (
    <section className="space-y-4">
      <h1 className="text-2xl font-bold">Votacion por categoria</h1>
      <p className="text-slate-300">Categoria actual: {categoryId}</p>

      <StateCard message="Estado: pendiente de conectar `GET /categories/{category}/next-item` y manejar `204` explicitamente." />
    </section>
  );
};
