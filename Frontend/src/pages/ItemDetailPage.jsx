import React from 'react';
import {Loader2, AlertTriangle} from 'lucide-react';
// Componentes
import CommentBox from '../components/comments/CommentBox';
import BackButton from "../components/ui/BackButton.jsx";
import FeedbackState from "../components/ui/FeedbackState.jsx";
import ItemDetailColumn from '../components/items/ItemDetailColumn.jsx';
// Hooks
import {useParams, useNavigate} from 'react-router-dom';
import {useItem} from '../hooks/items/useItemQueries';

const ItemDetailPage = () => {
  const {itemId} = useParams();
  const navigate = useNavigate();

  const {data: item, isLoading, isError} = useItem(itemId);


  return (
    <>
      <div className="flex flex-col w-full relative animate-fade-in pb-6 lg:pb-0 lg:h-page-content">
        <div className="shrink-0 mb-4 px-2 lg:px-0">
          <BackButton/>
        </div>
        {isLoading ? (<div className="flex min-h-state items-center justify-center">
          <FeedbackState
            icon={Loader2}
            title="Cargando detalles"
            description="Estamos preparando la información del ítem."
            isLoading
          />
        </div>) : (isError || !item) ? (
          <div className="flex min-h-state items-center justify-center">
            <FeedbackState
              icon={AlertTriangle}
              title="No se ha podido cargar el ítem"
              description="Intenta nuevamente en unos segundos."
              actionText="Volver"
              onAction={() => navigate(-1)}
            />
          </div>
        ) : (
          <div className="flex flex-col lg:flex-row gap-6 flex-1 min-h-0">
            <div className="w-full lg:w-2/3 h-full min-h-0">
              <ItemDetailColumn item={item}/>
            </div>
            <div
              className="flex flex-col w-full lg:w-1/3 h-[500px] lg:h-full bg-surface rounded-3xl shadow-xl ring-1 ring-slate-200 p-6 overflow-hidden min-h-0">
              <CommentBox itemId={item.id} className="h-full"/>
            </div>
          </div>
        )}
      </div>
    </>
  );
};

export default ItemDetailPage;