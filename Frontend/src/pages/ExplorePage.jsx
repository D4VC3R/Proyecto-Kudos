import React from 'react';
import { Compass, Telescope, Flame } from 'lucide-react';
// Componentes
import StaggerGrid from "../components/animations/StaggerGrid.jsx";
import StaggerItem from "../components/animations/StaggerItem.jsx";
import SectionHeader from '../components/ui/SectionHeader.jsx';
import BackButton from "../components/ui/BackButton.jsx";
import SearchFilter from '../components/ui/SearchFilter.jsx';
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
      <div className="flex flex-col gap-6 animate-fade-in relative container mx-auto px-4 py-8 max-w-7xl">
        <SectionHeader title="Explorar" highlight={state.displayCategoryName} icon={Compass}>
          <div className="flex gap-2 flex-wrap">
            <SelectFilter
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
            <SearchFilter
              value={state.searchInput}
              onChange={actions.handleSearchChange}
              placeholder="Buscar ítem..."
            />
          </div>
        </SectionHeader>

        <div className="flex flex-col gap-8 w-full mt-4">
          {state.isLoading ? (
            <FeedbackState icon={Telescope} isLoading title={`Explorando ${state.displayCategoryName}...`}  />
          ) : state.isError ? (
            <FeedbackState
              icon={Compass}
              title="Problemas de conexión"
              description="Algo no ha ido bien, inténtalo de nuevo más tarde."
              iconColorClass="bg-red-100 text-red-500"
            />
          ) : state.flattenedItems.length === 0 ? (
            <FeedbackState
              icon={Telescope}
              title="Buscas cosas muy raras, ¿no?"
              description="No se encontraron resultados con los filtros actuales."
            />
          ) : (
            <div className="overflow-x-hidden w-full py-4">
              <StaggerGrid className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 justify-center">
                {state.flattenedItems.map((item, index) => (
                  <StaggerItem key={item.id} alternate index={index}>
                    <ItemCard
                      item={item}
                      onClick={() => actions.handleItemClick(item)}
                    />
                  </StaggerItem>
                ))}
              </StaggerGrid>
            </div>
          )}

          {state.isFetchingNextPage && (
            <div className="py-4">
              <FeedbackState icon={Telescope} isLoading title="Cargando más resultados..." />
            </div>
          )}
          <div ref={refs.observerRef} className="h-4 w-full" />
        </div>
      </div>
    </>
  );
};

export default ExplorePage;