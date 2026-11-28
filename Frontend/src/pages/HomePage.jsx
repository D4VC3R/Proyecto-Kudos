import React from 'react';
import { Compass, ShieldAlert } from 'lucide-react';
// Componentes
import PageHeader from '../components/ui/PageHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import SliderSkeleton from '../components/ui/skeletons/SliderSkeleton.jsx';
import CategoryShortcuts from '../components/home/CategoryShortcuts.jsx';
import HomeSection from '../components/home/HomeSection.jsx';
import HomeRowSection from '../components/home/HomeRowSection.jsx';
import HomeRowGroup from '../components/home/HomeRowGroup.jsx';
import HomeRankingBand from '../components/home/HomeRankingBand.jsx';
import EditorialListCard from '../components/home/EditorialListCard.jsx';
// Hooks
import { useHomePage } from '../hooks/pages/useHomePage.js';

const HomePage = () => {
  const { state } = useHomePage();

  return (
    <div className={`flex w-full flex-col gap-12 transition-opacity duration-300 ${state.isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}>
      <div className="flex flex-col gap-7 pt-2">
        <PageHeader
          size="display"
          kicker={state.heroKicker}
          title="Descubre qué ver"
          subtitle="Recomendaciones, novedades y listas para encontrar tu próxima peli, serie, juego o destino."
        />
        <CategoryShortcuts categories={state.categories} />
      </div>

      {state.isLoading ? (
        <>
          <SliderSkeleton />
          <SliderSkeleton />
        </>
      ) : state.isError ? (
        <FeedbackState
          icon={ShieldAlert}
          titleAs="h2"
          title="Error"
          description="Servicio no disponible. Prueba de nuevo en unos minutos."
          iconColorClass="bg-danger text-canvas"
        />
      ) : state.isEmpty ? (
        <FeedbackState
          icon={Compass}
          titleAs="h2"
          title="Todavía no hay nada que mostrar"
          description="Vuelve en unos días o explora las categorías."
        />
      ) : (
        <>
          {state.forYouRows.length === 1 && !state.forYouRows[0].because && (
            <HomeRowSection id="home-for-you" title={state.forYouTitle} items={state.forYouRows[0].items} />
          )}
          {(state.forYouRows.length > 1 || (state.forYouRows.length === 1 && state.forYouRows[0].because)) && (
            <HomeSection id="home-for-you" title={state.forYouTitle}>
              <div className="flex flex-col gap-7">
                {state.forYouRows.map((row) => (
                  <HomeRowGroup
                    key={row.key}
                    id={`home-for-you-${row.key}`}
                    because={row.because}
                    items={row.items}
                    labelledBy="home-for-you"
                  />
                ))}
              </div>
            </HomeSection>
          )}

          <HomeRankingBand ranking={state.ranking} isAuthenticated={state.isAuthenticated} />

          {state.newReleases.length > 0 && (
            <HomeRowSection id="home-new-releases" title="Novedades" items={state.newReleases} />
          )}

          {state.topRated.length > 0 && (
            <HomeRowSection id="home-top-rated" title="Mejor valorados" items={state.topRated} />
          )}

          {state.editorialLists.length > 0 && (
            <HomeSection id="home-editorial-lists" title="Listas destacadas">
              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                {state.editorialLists.map((list) => (
                  <EditorialListCard key={list.id} list={list} />
                ))}
              </div>
            </HomeSection>
          )}
        </>
      )}
    </div>
  );
};

export default HomePage;
