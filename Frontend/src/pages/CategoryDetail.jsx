import React, { useMemo } from 'react';
import { useParams } from 'react-router-dom';
import { PlayCircle, PlusCircle } from 'lucide-react';
import { useCategoryDetail, useInfiniteCategoryRanking } from '../hooks/categories/useCategoryQueries';
import ActionButton from '../components/common/ActionButton';
import Ranking from '../components/ranking/Ranking.jsx';
import InfiniteSlider from '../components/common/InfiniteSlider';
import { Skeleton } from '../components/common/Skeleton';

const CategoryDetail = () => {
  const { categorySlug } = useParams();
  const itemsPerPage = 10;

  const { data: category, isLoading: isLoadingCategory, isFetching: isFetchingCategory } = useCategoryDetail(categorySlug);
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

  const rankingItems = useMemo(() => {
    if (!rankingData) return [];
    return rankingData.pages.flatMap(page => page.data.ranking);
  }, [rankingData]);

  const hasRankingData = rankingItems.length > 0;

  if (!isLoadingCategory && !category) {
    return (
      <div className="flex min-h-[50vh] flex-col items-center justify-center text-center">
        <h2 className="text-2xl font-bold text-slate-700">Categoría no encontrada</h2>
      </div>
    );
  }

  return (
    <div className={`mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 py-8 sm:px-6 lg:px-8 transition-opacity duration-300 ${(isFetchingRanking && !isFetchingNextPage && !isLoadingRanking) ? 'opacity-60' : 'opacity-100'}`}>

      <div className="text-center md:text-left h-[100px]">
        {isLoadingCategory || isFetchingCategory ? (
          <div className="flex flex-col gap-4">
            <Skeleton className="h-12 w-2/3 md:w-1/2" />
            <Skeleton className="h-6 w-full md:w-1/3" />
          </div>
        ) : (
          <>
            <h1 className="text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">
              Bienvenido a <span className="text-blue-600 drop-shadow-sm">{category?.name}</span>
            </h1>
            <p className="mt-2 text-lg font-medium text-slate-500">{category?.description}</p>
          </>
        )}
      </div>

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-12 md:items-center">
        <div className="flex flex-col gap-6 lg:col-span-5 items-center justify-center">
          <div className="w-full flex flex-col gap-6 rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
            <ActionButton
              title="Empezar a Votar"
              description="Enfréntate a la cola de votación y gana Kudos."
              icon={PlayCircle}
              to={`/${categorySlug}/vote`}
              color="blue"
            />
            <ActionButton
              title="Crear Propuesta"
              description="¿Falta tu favorito? Proponlo y gana puntos extra."
              icon={PlusCircle}
              to={`/${categorySlug}/proposals/new`}
              color="red"
            />
          </div>
        </div>

        <div className="lg:col-span-7">
          {(isLoadingRanking || (isFetchingRanking && !hasRankingData)) ? (
            <Skeleton className="h-[420px] w-full rounded-3xl shadow-2xl ring-2 ring-slate-200" />
          ) : isErrorRanking && !hasRankingData ? (
            <div className="flex min-h-[420px] items-center justify-center rounded-3xl bg-white p-6 text-center shadow-2xl ring-2 ring-slate-200">
              <p className="text-slate-600">Error: {rankingError?.message}</p>
            </div>
          ) : (
            <Ranking
              title="Top Ranking Global"
              items={rankingItems}
              fetchNextPage={fetchNextPage}
              hasNextPage={hasNextPage}
              isFetchingNextPage={isFetchingNextPage}
            />
          )}
        </div>
      </div>

      {isLoadingCategory ? (
        <Skeleton className="mt-8 h-64 w-full rounded-3xl" />
      ) : category?.items && category.items.length > 0 && (
        <div className="mt-8 rounded-3xl bg-white py-8 shadow-xl ring-1 ring-slate-200">
          <div className="mb-6 px-8 border-b border-slate-100 pb-4">
            <h3 className="text-2xl font-black text-slate-900">Candidatos Destacados</h3>
          </div>
          <InfiniteSlider items={category.items} />
        </div>
      )}
    </div>
  );
};

export default CategoryDetail;