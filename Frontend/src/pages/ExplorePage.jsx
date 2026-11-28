import React from 'react';
import { Compass, Telescope, Flame, Search } from 'lucide-react';
// Componentes
import PageHeader from '../components/ui/PageHeader.jsx';
import BackButton from "../components/ui/BackButton.jsx";
import Input from '../components/ui/Input.jsx';
import SelectFilter from '../components/ui/SelectFilter.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import ItemCard from '../components/items/ItemCard';
// Hooks
import { useExplorePage } from '../hooks/pages/useExplorePage.js';

const ExplorePage = () => {
  const { state, actions, refs } = useExplorePage();

  return (
    <>
      <BackButton />
      <div className="flex flex-col gap-8">
        <PageHeader title="Explorar" highlight={state.displayCategoryName}>
          <SelectFilter
            label="Ordenar por"
            icon={Flame}
            value={state.sortValue}
            onChange={actions.handleSortChange}
            defaultOption="Mejor valorados"
            options={[
              { value: 'recent|desc', label: 'Más recientes' },
              { value: 'recent|asc', label: 'Más antiguos' },
              { value: 'vote_avg|asc', label: 'Peor valorados' }
            ]}
          />
          <Input
            type="search"
            icon={Search}
            label="Buscar ítem"
            hideLabel
            placeholder="Buscar ítem..."
            value={state.searchInput}
            onChange={actions.handleSearchChange}
            className="w-full sm:w-60"
          />
        </PageHeader>

        {state.isLoading ? (
          <FeedbackState icon={Telescope} isLoading titleAs="h2" title={`Explorando ${state.displayCategoryName}...`} />
        ) : state.isError ? (
          <FeedbackState
            icon={Compass}
            titleAs="h2"
            title="Problemas de conexión"
            description="Algo no ha ido bien, inténtalo de nuevo más tarde."
            iconColorClass="bg-danger text-canvas"
          />
        ) : state.flattenedItems.length === 0 ? (
          <FeedbackState
            icon={Telescope}
            titleAs="h2"
            title="Buscas cosas muy raras, ¿no?"
            description="No se encontraron resultados con los filtros actuales."
          />
        ) : (
          <ul data-explore-grid className="grid grid-cols-2 gap-x-3 gap-y-6 sm:grid-cols-3 md:grid-cols-4 md:gap-x-4 md:gap-y-8 lg:grid-cols-6">
            {state.flattenedItems.map((item, index) => (
              <li key={item.id} className="min-w-0">
                <ItemCard item={item} index={index} />
              </li>
            ))}
          </ul>
        )}

        {state.isFetchingNextPage && (
          <FeedbackState icon={Telescope} isLoading titleAs="h2" title="Cargando más resultados..." />
        )}
        <div ref={refs.observerRef} className="h-4 w-full" />
      </div>
    </>
  );
};

export default ExplorePage;