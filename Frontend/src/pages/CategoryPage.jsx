import React, { useMemo } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useInfiniteCategoryRanking } from '../hooks/categories/useCategoryQueries';
import { useInfiniteItems } from '../hooks/items/useItemQueries'; // Importamos el hook general de ítems
import Ranking from '../components/ranking/Ranking.jsx';
import InfiniteItemSlider from '../components/items/InfiniteItemSlider.jsx';
import { Skeleton } from '../components/common/Skeleton';
import { SectionHeader } from "../components/common/SectionHeader.jsx";
import ActionMenu from "../components/common/ActionMenu.jsx";
import { FeedbackState } from "../components/common/FeedbackState.jsx";
import { Telescope } from 'lucide-react';

const CategoryPage = () => {
  const { categorySlug } = useParams();
  const navigate = useNavigate();
  const itemsPerPage = 12;

  const {
    data: rankingData,
    isLoading: isLoadingRanking,
    isFetching: isFetchingRanking,
    isFetchingNextPage: isFetchingNextRanking,
    hasNextPage: hasNextRanking,
    fetchNextPage: fetchNextRanking,
    isError: isErrorRanking,
    error: rankingError
  } = useInfiniteCategoryRanking(categorySlug, itemsPerPage);

  const {
    data: detailedItemsData,
    isLoading: isLoadingDetailed,
    isError: isErrorDetailed
  } = useInfiniteItems(
    {
      category_slug: categorySlug,
      sort_by: 'vote_avg',
      sort_order: 'desc',
      per_page: 20
    },
    { enabled: !!categorySlug }
  );

  // Memoización de datos del Ranking
  const category = useMemo(() => {
    return rankingData?.pages?.[0]?.data?.category || null;
  }, [rankingData]);

  const rankingItems = useMemo(() => {
    if (!rankingData) return [];
    return rankingData.pages.flatMap(page => page.data.ranking);
  }, [rankingData]);

  // Memoización de datos detallados para el Slider
  const sliderItems = useMemo(() => {
    if (!detailedItemsData) return [];
    // Aplanamos las páginas y tomamos los primeros 20
    return detailedItemsData.pages.flatMap(page => page.data).slice(0, 20);
  }, [detailedItemsData]);

  const hasRankingData = rankingItems.length > 0;

  const handleItemClick = (item) => {
    navigate(`/${categorySlug}/item/${item.id}`);
  };

  //  Falla rápido si ranking/categoría no existe
  if (!isLoadingRanking && !category && !isErrorRanking) {
    return (
      <div className="flex min-h-[60vh] w-full items-center justify-center px-4">
        <FeedbackState
          icon={Telescope}
          iconColorClass="bg-slate-100 text-text-normal"
          title="Categoría no encontrada"
          description="Parece que la categoría que buscas no existe o ha sido eliminada."
          actionText="Volver al inicio"
          onAction={() => navigate('/')}
        />
      </div>
    );
  }

  return (
    <div className={`flex w-full flex-col transition-opacity duration-300 ${(isFetchingRanking && !isFetchingNextRanking && !isLoadingRanking) ? 'opacity-60' : 'opacity-100'}`}>


      <div className="mb-8 shrink-0">
        {isLoadingRanking ? (
          <div className="flex flex-col gap-4 border-b border-border pb-4">
            <Skeleton className="h-8 w-2/3 md:w-1/3" />
            <Skeleton className="h-4 w-full md:w-1/2" />
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
        <ActionMenu categorySlug={categorySlug} name={category?.name} />

        <div className="lg:col-span-7">
          {isLoadingRanking || (isFetchingRanking && !hasRankingData) ? (
            <Skeleton className="h-[750px] w-full rounded-3xl shadow-2xl ring-2 ring-slate-200" />
          ) : isErrorRanking && !hasRankingData ? (
            <div className="flex min-h-[420px] items-center justify-center rounded-3xl bg-surface p-6 text-center shadow-2xl ring-2 ring-slate-200">
              <p className="text-nav-item">Error al cargar el ranking: {rankingError?.message}</p>
            </div>
          ) : (
            <Ranking
              title="Top Ranking Global"
              items={rankingItems}
              itemsPerPage={itemsPerPage}
              fetchNextPage={fetchNextRanking}
              hasNextPage={hasNextRanking}
              isFetchingNextPage={isFetchingNextRanking}
              onItemClick={handleItemClick}
            />
          )}
        </div>
      </div>


      {isLoadingDetailed ? (
        <Skeleton className="mt-8 h-64 w-full rounded-3xl" />
      ) : isErrorDetailed ? (
        <div className="mt-8 flex h-64 items-center justify-center rounded-3xl bg-surface shadow-xl ring-1 ring-slate-200">
          <p className="text-text-normal text-sm">No se pudieron cargar los candidatos destacados.</p>
        </div>
      ) : sliderItems.length > 0 && (
        <div className="mt-8 rounded-3xl bg-surface py-8 shadow-xl ring-1 ring-slate-200">
          <div className="mb-6 px-8 border-b border-slate-100 pb-4">
            <h3 className="text-2xl font-black text-text-highlight">Candidatos Destacados</h3>
          </div>
          <InfiniteItemSlider items={sliderItems} onItemClick={handleItemClick} />
        </div>
      )}

    </div>
  );
};

export default CategoryPage;