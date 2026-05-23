import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {  Star, Loader2, Info, AlertTriangle } from 'lucide-react';
import { useItem } from '../hooks/items/useItemQueries';
import { CommentBox } from '../components/comments/CommentBox';
import { ItemImage } from "../components/items/ItemImage.jsx";
import { BackButton } from "../components/common/BackButton.jsx";
import { FeedbackState } from "../components/common/FeedbackState.jsx";

const ItemDetailPage = () => {
  const {  itemId } = useParams();
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
    <div className="flex flex-col gap-8 w-full max-w-4xl mx-auto animate-fade-in relative">


      <div className="w-full">
        <BackButton />
      </div>


      <div className="w-full bg-white rounded-3xl shadow-xl ring-1 ring-slate-200 overflow-hidden">
        <ItemImage item={item} showTitle={false} />


        <div className="p-6 md:p-10 flex flex-col gap-6">
          <div className="flex flex-col gap-2">

            <h1 className="text-3xl md:text-5xl font-black text-slate-900 leading-tight">
              {item.name}
            </h1>

            <div className="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                <Star size={18} className="text-yellow-500" />
                <div>
                  <p className="text-xs font-semibold text-slate-500">Valoración media</p>
                  <p className="text-lg font-black text-slate-900">{item.vote_avg?.toFixed(1) || '0.0'}</p>
                </div>
              </div>
              <div className="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                <Star size={18} className="text-blue-600" />
                <div>
                  <p className="text-xs font-semibold text-slate-500">Votos</p>
                  <p className="text-lg font-black text-slate-900">{item.vote_count ?? 0}</p>
                </div>
              </div>
            </div>
            {item.creator && (
              <p className="text-slate-500 flex items-center gap-2">
                <Info size={16} />
                Aportado por <span className="font-bold text-slate-700">{item.creator.name}</span>
              </p>
            )}
          </div>

          <div className="prose prose-slate max-w-none prose-p:leading-relaxed prose-p:text-slate-600">
            <p className="whitespace-pre-line text-lg font-medium">{item.description}</p>
          </div>
        </div>
      </div>

      <div className="w-full bg-white rounded-3xl p-6 md:p-10 shadow-xl ring-1 ring-slate-200">
        <CommentBox itemId={item.id} />
      </div>

    </div>
  );
}

export default ItemDetailPage;