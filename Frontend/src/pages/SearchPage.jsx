import React from 'react';
import { Search, SearchX, Telescope } from 'lucide-react';
// Componentes
import PageHeader from '../components/ui/PageHeader.jsx';
import SectionHead from '../components/ui/SectionHead.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import ItemGrid, { ITEM_GRID_CLASSES } from '../components/items/ItemGrid.jsx';
import Skeleton from '../components/ui/skeletons/Skeleton.jsx';
import CatalogImportPanel from '../components/catalog/CatalogImportPanel.jsx';
// Hooks
import { useSearchPage } from '../hooks/pages/useSearchPage.js';

const SearchPage = () => {
  const { state, actions, refs } = useSearchPage();

  return (
    <div className="flex flex-col gap-8 animate-fade-in">
      <PageHeader
        title={state.term ? 'Resultados para' : 'Buscar'}
        highlight={state.term ? `«${state.term}»` : undefined}
      />

      {!state.term ? (
        <FeedbackState
          icon={Search}
          titleAs="h2"
          title="¿Qué estás buscando?"
          description="Escribe al menos 2 caracteres en el buscador."
        />
      ) : state.isLoading ? (
        <div className="flex flex-col gap-8">
          {[0, 1].map((block) => (
            <div key={block} className="flex flex-col gap-4">
              <Skeleton className="h-7 w-48 rounded-sm" />
              <div className={ITEM_GRID_CLASSES}>
                {[0, 1, 2, 3, 4, 5].map((card) => (
                  <Skeleton key={card} className="w-full aspect-poster rounded-md border border-line" />
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : state.isError ? (
        <FeedbackState
          icon={Telescope}
          titleAs="h2"
          title="Problemas de conexión"
          description="Algo no ha ido bien, inténtalo de nuevo más tarde."
          iconColorClass="bg-danger text-canvas"
        />
      ) : state.groups.length === 0 ? (
        <FeedbackState
          icon={SearchX}
          titleAs="h2"
          title="Sin resultados"
          description={`No hemos encontrado nada para «${state.term}».`}
        />
      ) : (
        state.groups.map((group, index) => (
          <section key={group.key} aria-labelledby={`search-group-${index}`} className="flex flex-col gap-4">
            <SectionHead id={`search-group-${index}`} title={group.category?.name ?? 'Sin categoría'} />
            <ItemGrid items={group.items} />
          </section>
        ))
      )}

      {state.catalog.isVisible && <CatalogImportPanel catalog={state.catalog} term={state.term} actions={actions} />}

      {state.isFetchingNextPage && (
        <div className="py-4">
          <FeedbackState icon={Telescope} isLoading titleAs="h2" title="Cargando más resultados..." />
        </div>
      )}
      <div ref={refs.observerRef} className="h-4 w-full" />
    </div>
  );
};

export default SearchPage;
