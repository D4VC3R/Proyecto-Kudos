import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Star, Loader2, Info, AlertTriangle } from 'lucide-react';
import { useItem } from '../hooks/items/useItemQueries';
import { CommentBox } from '../components/comments/CommentBox';
import { ItemImage } from "../components/items/ItemImage.jsx";
import { BackButton } from "../components/common/BackButton.jsx";
import { FeedbackState } from "../components/common/FeedbackState.jsx";

const ItemDetailPage = () => {
  const { itemId } = useParams();
  const navigate = useNavigate();

  const { data: item, isLoading, isError } = useItem(itemId);

  if (isLoading) {
    return (
      <div className="flex min-h-[60vh] items-center justify-center">
        <FeedbackState
          icon={Loader2}
          title="Cargando detalles"
          description="Estamos preparando la información del ítem."
          isLoading
        />
      </div>
    );
  }

  if (isError || !item) {
    return (
      <div className="flex min-h-[60vh] items-center justify-center">
        <FeedbackState
          icon={AlertTriangle}
          title="No se ha podido cargar el ítem"
          description="Intenta nuevamente en unos segundos."
          actionText="Volver"
          onAction={() => navigate(-1)}
        />
      </div>
    );
  }

  return (
    <div className="flex flex-col w-full max-w-7xl mx-auto relative animate-fade-in pb-6 lg:pb-0 lg:h-[calc(100dvh-9rem)]">

      <div className="shrink-0 mb-4 px-2">
        <BackButton />
      </div>

      <div className="flex flex-col lg:flex-row gap-6 flex-1 min-h-0">

        <div className="flex flex-col w-full lg:w-3/4 h-full bg-surface rounded-3xl shadow-xl ring-1 ring-slate-200 p-6 md:p-8 lg:overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200">

          <div className="flex flex-col gap-6 mb-8">
            <h1 className="text-3xl md:text-5xl lg:text-6xl font-black text-text-highlight leading-tight">
              {item.name}
            </h1>

            {/* Metadatos rediseñados en línea para ahorrar espacio vertical */}
            <div className="flex flex-wrap items-center gap-3">
              <span className="flex items-center gap-2 rounded-full bg-yellow-50 px-4 py-2 text-sm font-bold text-yellow-800 ring-1 ring-yellow-300">
                <Star size={18} className="text-yellow-600 fill-yellow-500" />
                {item.vote_avg?.toFixed(1) || '0.0'} ({item.vote_count ?? 0} votos)
              </span>

              {item.creator && (
                <span className="flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200">
                  <Info size={16} className="text-slate-500" />
                  Aportado por <strong>{item.creator.name}</strong>
                </span>
              )}
            </div>
          </div>

          <div className="w-full flex justify-center mb-8">
            <ItemImage item={item} showTitle={false} />
          </div>

          {/* Caja de descripción destacada */}
          <div className="prose prose-slate max-w-none mt-auto">
            <div className="bg-slate-50 rounded-2xl border border-slate-100 p-6">
              <h3 className="text-lg font-bold text-slate-800 mb-3">Acerca de este candidato</h3>
              <p className="whitespace-pre-line text-lg text-slate-700 font-medium leading-relaxed">
                {item.description}
              </p>
            </div>
          </div>
        </div>

        <div className="flex flex-col w-full lg:w-1/4 h-[500px] lg:h-full bg-surface rounded-3xl shadow-xl ring-1 ring-slate-200 p-6 overflow-hidden">
          <CommentBox itemId={item.id} className="h-full" />
        </div>

      </div>
    </div>
  );
}

export default ItemDetailPage;