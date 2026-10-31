import React from 'react';
import { Compass, ShieldAlert } from 'lucide-react';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import SliderSkeleton from '../components/ui/skeletons/SliderSkeleton.jsx';
import CategoryShortcuts from '../components/home/CategoryShortcuts.jsx';
import HomeSection from '../components/home/HomeSection.jsx';
import HomeItemRow from '../components/home/HomeItemRow.jsx';
import EditorialListCard from '../components/home/EditorialListCard.jsx';
// Hooks
import { useHomePage } from '../hooks/pages/useHomePage.js';

const HomePage = () => {
  const { state, actions } = useHomePage();

  return (
    <div className={`flex w-full flex-col gap-10 transition-opacity duration-300 ${state.isBackgroundUpdating ? 'opacity-60' : 'opacity-100'}`}>
      <SectionHeader
        size="large"
        title="Descubre"
        highlight="qué ver"
        subtitle="Recomendaciones, novedades y listas para encontrar tu próxima peli o serie."
      />

      <CategoryShortcuts categories={state.categories} />

      {state.isLoading ? (
        <>
          <SliderSkeleton />
          <SliderSkeleton />
        </>
      ) : state.isError ? (
        <FeedbackState
          icon={ShieldAlert}
          title="Error"
          description="Servicio no disponible. Prueba de nuevo en unos minutos."
          iconColorClass="bg-red-500 text-white"
        />
      ) : state.isEmpty ? (
        <FeedbackState
          icon={Compass}
          title="Todavía no hay nada que mostrar"
          description="Vuelve en unos días o explora las categorías."
        />
      ) : (
        <>
          {state.forYouRows.length > 0 && (
            <HomeSection id="home-for-you" title={state.forYouTitle}>
              {state.forYouRows.map((row) => (
                <HomeItemRow
                  key={row.key}
                  items={row.items}
                  label={row.label}
                  labelId={`home-for-you-${row.key}`}
                  onItemClick={actions.handleItemClick}
                />
              ))}
            </HomeSection>
          )}

          {state.newReleases.length > 0 && (
            <HomeSection id="home-new-releases" title="Novedades">
              <HomeItemRow items={state.newReleases} onItemClick={actions.handleItemClick} />
            </HomeSection>
          )}

          {state.editorialLists.length > 0 && (
            <HomeSection id="home-editorial-lists" title="Listas destacadas">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {state.editorialLists.map((list) => (
                  <EditorialListCard key={list.id} list={list} onItemClick={actions.handleItemClick} />
                ))}
              </div>
            </HomeSection>
          )}

          {state.topRated.length > 0 && (
            <HomeSection id="home-top-rated" title="Mejor valorados">
              <HomeItemRow items={state.topRated} onItemClick={actions.handleItemClick} />
            </HomeSection>
          )}
        </>
      )}
    </div>
  );
};

export default HomePage;
