import React from 'react';
import { StaggerGrid } from '../components/animations/StaggerGrid.jsx';
import { CategoryCard } from '../components/category/CategoryCard';
import { CategoryCardSkeleton } from '../components/category/CategoryCardSkeleton';
import { useCategories } from '../hooks/categories/useCategoryQueries';
import {SectionHeader} from "../components/common/SectionHeader.jsx";
import {StaggerItem} from "../components/animations/StaggerItem.jsx";

// Página principal que muestra las categorías disponibles, utiliza el hook useCategories() para obtener los datos necesarios y renderiza un grid de CategoryCard.
// Si los datos están cargando, muestra skeletons de carga. Si hay un error, muestra un mensaje de error centrado.
const HomePage = () => {
  const { data: categories, isLoading, isFetching, isError } = useCategories();

  if (isError) {
    return (
      <div className="flex justify-center items-center py-20 min-h-[50vh]">
        <p className="text-red-500 font-medium">Hubo un error recuperando la información del servidor. Prueba de nuevo en unos instantes.</p>
      </div>
    );
  }

  return (
    <div className={`flex w-full flex-col transition-opacity duration-300 ${isFetching && !isLoading ? 'opacity-60' : 'opacity-100'}`}>

      <div className="mb-8 shrink-0">
        <SectionHeader
          size="large"
          title="Elige tu"
          highlight="Temática"
          subtitle="Explora las diferentes categorías, vota por los mejores ítems y gana Kudos para subir en el ranking mundial."
        />
      </div>

      <StaggerGrid className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-10">
        {isLoading ? (
          Array.from({ length: 9 }).map((_, i) => <CategoryCardSkeleton key={i} />)
        ) : (
          Array.isArray(categories) && categories.map((category) => (
            <StaggerItem key={category.id}>
              <CategoryCard category={category} />
            </StaggerItem>
          ))
        )}
      </StaggerGrid>
    </div>
  );
};

export default HomePage;