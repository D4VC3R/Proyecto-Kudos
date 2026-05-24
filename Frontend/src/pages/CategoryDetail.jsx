import React, {useMemo} from 'react';
import {useNavigate, useParams} from 'react-router-dom';
import {useInfiniteCategoryRanking} from '../hooks/categories/useCategoryQueries';
import Ranking from '../components/ranking/Ranking.jsx';
import InfiniteItemSlider from '../components/items/InfiniteItemSlider.jsx';
import {Skeleton} from '../components/common/Skeleton';
import {SectionHeader} from "../components/common/SectionHeader.jsx";
import ActionMenu from "../components/common/ActionMenu.jsx";
import {FeedbackState} from "../components/common/FeedbackState.jsx";
import {Telescope} from 'lucide-react';

const CategoryDetail = () => {
  const {categorySlug} = useParams();
  const itemsPerPage = 16;
  const navigate = useNavigate();

  const {
    data: rankingData,
    isLoading: isLoadingRanking,
    isFetching: isFetchingRanking,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage,
    isError: isErrorRanking,
    error: rankingError
  } = useInfiniteCategoryRanking(categorySlug, itemsPerPage);

  // Se extrae la información de la categoría desde la primera página de datos.
  const category = useMemo(() => {
    return rankingData?.pages?.[0]?.data?.category || null;
  }, [rankingData]);

  // Sacamos los items de todas las páginas y los aplanamos en un solo array para pasarlos al Ranking
  const rankingItems = useMemo(() => {
    if (!rankingData) return [];
    return rankingData.pages.flatMap(page => page.data.ranking);
  }, [rankingData]);

  // Se limita el número de items destacados a los 20 primeros del ranking para mostrar en el slider.
  const sliderItems = useMemo(() => {
    return rankingItems.slice(0, 20);
  }, [rankingItems]);

  const hasRankingData = rankingItems.length > 0;

  if (!isLoadingRanking && !category) {
    return (
        <div className="flex min-h-[60vh] w-full items-center justify-center px-4">
          <FeedbackState
              icon={Telescope}
              iconColorClass="bg-slate-100 text-slate-500"
              title="Categoría no encontrada"
              description="Parece que la categoría que buscas no existe o ha sido eliminada."
              actionText="Volver al inicio"
              onAction={() => navigate('/')}
          />
        </div>
    );
  }

  return (
      <div className={`flex w-full flex-col transition-opacity duration-300 ${(isFetchingRanking && !isFetchingNextPage && !isLoadingRanking) ? 'opacity-60' : 'opacity-100'}`}>
        <div className="mb-8 shrink-0">
          {isLoadingRanking ? (
              <div className="flex flex-col gap-4 border-b border-slate-200 pb-4">
                <Skeleton className="h-8 w-2/3 md:w-1/3"/>
                <Skeleton className="h-4 w-full md:w-1/2"/>
              </div>
          ) : (
              <SectionHeader
                  size="large"
                  title="Bienvenido a"
                  highlight={category?.name}
                  subtitle={category?.description}
              />
          )}
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-12 items-start">
          <ActionMenu categorySlug={categorySlug} name={category?.name}/>
          <div className="lg:col-span-7">
            {(isLoadingRanking || (isFetchingRanking && !hasRankingData)) ? (
                <Skeleton className="h-[750px] w-full rounded-3xl shadow-2xl ring-2 ring-slate-200"/>
            ) : isErrorRanking && !hasRankingData ? (
                <div className="flex min-h-[420px] items-center justify-center rounded-3xl bg-white p-6 text-center shadow-2xl ring-2 ring-slate-200">
                  <p className="text-slate-600">Error: {rankingError?.message}</p>
                </div>
            ) : (
                <Ranking
                    title="Top Ranking Global"
                    items={rankingItems}
                    itemsPerPage={12}
                    fetchNextPage={fetchNextPage}
                    hasNextPage={hasNextPage}
                    isFetchingNextPage={isFetchingNextPage}
                />
            )}
          </div>
        </div>

        {isLoadingRanking ? (
            <Skeleton className="mt-8 h-64 w-full rounded-3xl"/>
        ) : sliderItems.length > 0 && (
            <div className="mt-8 rounded-3xl bg-white py-8 shadow-xl ring-1 ring-slate-200">
              <div className="mb-6 px-8 border-b border-slate-100 pb-4">
                <h3 className="text-2xl font-black text-slate-900">Candidatos Destacados</h3>
              </div>
              <InfiniteItemSlider items={sliderItems}/>
            </div>
        )}
      </div>
  );
};

export default CategoryDetail;