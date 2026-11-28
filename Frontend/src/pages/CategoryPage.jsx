import React from 'react';
import { Telescope } from 'lucide-react';
// Componentes
import Ranking from '../components/ui/ranking/Ranking.jsx';
import SliderSkeleton from "../components/ui/skeletons/SliderSkeleton.jsx";
import HeaderSkeleton from "../components/ui/skeletons/HeaderSkeleton.jsx";
import RankingSkeleton from "../components/ui/skeletons/RankingSkeleton.jsx";
import PageHeader from "../components/ui/PageHeader.jsx";
import Card from "../components/ui/Card.jsx";
import ActionMenu from "../components/ui/ActionMenu.jsx";
import FeedbackState from "../components/ui/FeedbackState.jsx";
import HomeRowSection from "../components/home/HomeRowSection.jsx";
// Hooks
import { useCategoryPage } from '../hooks/pages/useCategoryPage.js';

const CategoryPage = () => {
  const { state, actions } = useCategoryPage();

  if (state.isLoadingCategory) {
    return (
      <div className="flex w-full flex-col gap-12" aria-busy="true">
        <HeaderSkeleton />
        <SliderSkeleton />
        <RankingSkeleton />
      </div>
    );
  }

  if (state.showNotFound) {
    return (
      <div className="flex min-h-[60vh] w-full items-center justify-center px-4">
        <FeedbackState
          icon={Telescope}
          title="Categoría no encontrada"
          description="Parece que la categoría que buscas no existe o ha sido eliminada."
          actionText="Volver al inicio"
          onAction={actions.handleGoHome}
        />
      </div>
    );
  }

  return (
    <div className={`flex w-full flex-col gap-12 transition-opacity duration-300 ${state.isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}>
      <div className="flex flex-col gap-7 pt-2">
        <PageHeader kicker="Categoría" title={state.category?.name} subtitle={state.category?.description} />
        <ActionMenu categorySlug={state.categorySlug} name={state.category?.name} />
      </div>

      {state.isLoadingDetailed ? (
        <SliderSkeleton />
      ) : state.isErrorDetailed ? (
        <Card><p className="text-small text-ink-muted">No se pudieron cargar los ítems destacados.</p></Card>
      ) : state.sliderItems.length > 0 && (
        <HomeRowSection id="category-featured" title="Destacados" items={state.sliderItems} />
      )}

      {state.isLoadingRanking || (state.isFetchingRanking && !state.hasRankingData) ? (
        <RankingSkeleton />
      ) : state.isErrorRanking && !state.hasRankingData ? (
        <Card><p className="text-small text-ink-muted">Error al cargar el ranking: {state.rankingError?.message}</p></Card>
      ) : (
        <Ranking
          title={state.category?.name}
          items={state.rankingItems}
          categorySlug={state.categorySlug}
          hasNextPage={state.hasNextRanking}
          isFetchingNextPage={state.isFetchingNextRanking}
          fetchNextPage={actions.fetchNextRanking}
        />
      )}
    </div>
  );
};

export default CategoryPage;
