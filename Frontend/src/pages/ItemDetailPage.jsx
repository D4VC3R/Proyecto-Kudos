import React, {useEffect} from 'react';
import {Loader2, AlertTriangle} from 'lucide-react';
// Componentes
import FeedbackState from "../components/ui/FeedbackState.jsx";
import ItemDetailColumn from '../components/items/ItemDetailColumn.jsx';
import ItemDetailPoster from '../components/items/ItemDetailPoster.jsx';
import ItemAttribution from '../components/items/ItemAttribution.jsx';
import SimilarItemsRow from '../components/items/SimilarItemsRow.jsx';
import {FOCUS_RING} from '../components/layout/headerStyles.js';
import {hasAttribution} from '../lib/attribution.js';
// Hooks
import {useParams, useNavigate, Link} from 'react-router-dom';
import {useItem, useSimilarItems} from '../hooks/items/useItemQueries';

const CRUMB_LINK = 'rounded-sm underline-offset-3 hover:text-ink hover:underline';

const ItemDetailPage = () => {
  const {itemId} = useParams();
  const navigate = useNavigate();

  const {data: item, isLoading, isError, error} = useItem(itemId);
  const {data: similarItems = []} = useSimilarItems(itemId);
  const isNotFound = error?.status === 404;

  // Al abrir otro ítem desde la fila de similares la ruta es la misma y el componente no se desmonta: vuelve arriba.
  useEffect(() => {
    window.scrollTo({top: 0});
  }, [itemId]);

  return (
    <>
      <div className="animate-fade-in pb-6">
        {item && (
          <nav aria-label="Migas de pan" className="mb-5 text-small text-ink-muted">
            <ol className="flex flex-wrap items-center gap-x-2 gap-y-1">
              <li>
                <Link to="/" className={`${CRUMB_LINK} ${FOCUS_RING}`}>Inicio</Link>
              </li>
              <li aria-hidden="true">/</li>
              {item.category?.slug && (
                <>
                  <li>
                    <Link to={`/${item.category.slug}`} className={`${CRUMB_LINK} ${FOCUS_RING}`}>{item.category.name}</Link>
                  </li>
                  <li aria-hidden="true">/</li>
                </>
              )}
              <li aria-current="page" className="text-ink">{item.name}</li>
            </ol>
          </nav>
        )}
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
              title={isNotFound ? 'Ítem no encontrado' : 'No se ha podido cargar el ítem'}
              description={isNotFound ? 'Este ítem no existe o ya no está disponible.' : 'Intenta nuevamente en unos segundos.'}
              actionText="Volver"
              onAction={() => navigate(-1)}
            />
          </div>
        ) : (
          <>
            <div className="grid gap-6 lg:grid-cols-detail lg:items-start lg:gap-14">
              <ItemDetailPoster key={`poster-${item.id}`} item={item}/>
              <ItemDetailColumn key={`detail-${item.id}`} item={item}/>
            </div>
            {similarItems.length > 0 && (
              <div className="mt-12">
                <SimilarItemsRow items={similarItems}/>
              </div>
            )}
            {hasAttribution(item.attribution) && (
              <div className="mt-8 border-t border-line pt-3">
                <ItemAttribution attribution={item.attribution}/>
              </div>
            )}
          </>
        )}
      </div>
    </>
  );
};

export default ItemDetailPage;