import React from 'react';
import { Telescope } from 'lucide-react';
// Componentes
import Ranking from '../components/ranking/Ranking.jsx';
import InfiniteItemSlider from '../components/items/InfiniteItemSlider.jsx';
import Skeleton from '../components/ui/Skeleton.jsx';
import SectionHeader from "../components/ui/SectionHeader.jsx";
import ActionMenu from "../components/ui/ActionMenu.jsx";
import FeedbackState from "../components/ui/FeedbackState.jsx";
// Hooks
import { useCategoryPage } from '../hooks/pages/useCategoryPage.js';

const CategoryPage = () => {
  const { state, actions } = useCategoryPage();

  if (state.showNotFound) {
    return (
      <div className="flex min-h-[60vh] w-full items-center justify-center px-4">
        <FeedbackState
          icon={Telescope}
          iconColorClass="bg-slate-100 text-text-normal"
          title="Categoría no encontrada"
          description="Parece que la categoría que buscas no existe o ha sido eliminada."
          actionText="Volver al inicio"
          onAction={actions.handleGoHome}
        />
      </div>
    );
  }

  return (
    <div className={`flex w-full flex-col transition-opacity duration-300 ${state.isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}>

      <div className="mb-8 shrink-0">
        {state.isLoadingRanking ? (
          <div className="flex flex-col gap-4 border-b border-border pb-4">
            <Skeleton className="h-8 w-2/3 md:w-1/3" />
            <Skeleton className="h-4 w-full md:w-1/2" />
          </div>
        ) : (
          <SectionHeader
            size="large"
            title="Bienvenido a"
            highlight={state.category?.name}
            subtitle={state.category?.description}
          />
        )}
      </div>

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-12 items-start">
        <ActionMenu categorySlug={state.categorySlug} name={state.category?.name} />

        <div className="lg:col-span-7">
          {state.isLoadingRanking || (state.isFetchingRanking && !state.hasRankingData) ? (
            <Skeleton className="h-[750px] w-full rounded-3xl shadow-2xl ring-2 ring-slate-200" />
          ) : state.isErrorRanking && !state.hasRankingData ? (
            <div className="flex min-h-[420px] items-center justify-center rounded-3xl bg-surface p-6 text-center shadow-2xl ring-2 ring-slate-200">
              <p className="text-nav-item">Error al cargar el ranking: {state.rankingError?.message}</p>
            </div>
          ) : (
            <Ranking
              title="Top Ranking Global"
              items={state.rankingItems}
              itemsPerPage={state.itemsPerPage}
              fetchNextPage={actions.fetchNextRanking}
              hasNextPage={state.hasNextRanking}
              isFetchingNextPage={state.isFetchingNextRanking}
              onItemClick={actions.handleItemClick}
            />
          )}
        </div>
      </div>

      {state.isLoadingDetailed ? (
        <Skeleton className="mt-8 h-64 w-full rounded-3xl" />
      ) : state.isErrorDetailed ? (
        <div className="mt-8 flex h-64 items-center justify-center rounded-3xl bg-surface shadow-xl ring-1 ring-slate-200">
          <p className="text-text-normal text-sm">No se pudieron cargar los candidatos destacados.</p>
        </div>
      ) : state.sliderItems.length > 0 && (
        <div className="mt-8 rounded-3xl bg-surface py-8 shadow-xl ring-1 ring-slate-200">
          <div className="mb-6 px-8 border-b border-slate-100 pb-4">
            <h3 className="text-2xl font-black text-text-highlight text-primary">Destacados</h3>
          </div>
          <InfiniteItemSlider items={state.sliderItems} onItemClick={actions.handleItemClick} />
        </div>
      )}

    </div>
  );
};

export default CategoryPage;