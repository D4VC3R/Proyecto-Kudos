import React from 'react';
import { Search, SearchX, Telescope } from 'lucide-react';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import ItemCard from '../components/items/ItemCard';
import Skeleton from '../components/ui/skeletons/Skeleton.jsx';
// Hooks
import { useSearchPage } from '../hooks/pages/useSearchPage.js';

const GRID_CLASSES = "grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6";

const SearchPage = () => {
  const { state, actions, refs } = useSearchPage();

  return (
    <div className="flex flex-col gap-8 animate-fade-in">
      <SectionHeader
        title={state.term ? 'Resultados para' : 'Buscar'}
        highlight={state.term ? `«${state.term}»` : undefined}
      />

      {!state.term ? (
        <FeedbackState
          icon={Search}
          title="¿Qué estás buscando?"
          description="Escribe al menos 2 caracteres en el buscador."
        />
      ) : state.isLoading ? (
        <div className="flex flex-col gap-8">
          {[0, 1].map((block) => (
            <div key={block} className="flex flex-col gap-4">
              <Skeleton className="h-7 w-48 rounded-lg" />
              <div className={GRID_CLASSES}>
                {[0, 1, 2, 3, 4].map((card) => (
                  <Skeleton key={card} className="w-full aspect-[3/4] rounded-3xl ring-1 ring-slate-200" />
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : state.isError ? (
        <FeedbackState
          icon={Telescope}
          title="Problemas de conexión"
          description="Algo no ha ido bien, inténtalo de nuevo más tarde."
          iconColorClass="bg-red-100 text-red-500"
        />
      ) : state.groups.length === 0 ? (
        <FeedbackState
          icon={SearchX}
          title="Sin resultados"
          description={`No hemos encontrado nada para «${state.term}».`}
        />
      ) : (
        state.groups.map((group) => (
          <section key={group.key} className="flex flex-col gap-4">
            <h3 className="text-xl font-black tracking-tight text-text-highlight">
              {group.category?.name ?? 'Sin categoría'}
            </h3>
            <div className={GRID_CLASSES}>
              {group.items.map((item) => (
                <ItemCard key={item.id} item={item} onClick={() => actions.handleItemClick(item)} />
              ))}
            </div>
          </section>
        ))
      )}

      {state.isFetchingNextPage && (
        <div className="py-4">
          <FeedbackState icon={Telescope} isLoading title="Cargando más resultados..." />
        </div>
      )}
      <div ref={refs.observerRef} className="h-4 w-full" />
    </div>
  );
};

export default SearchPage;
