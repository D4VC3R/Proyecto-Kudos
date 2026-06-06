import React from 'react';
// Componentes
import StaggerGrid from '../components/animations/StaggerGrid.jsx';
import CategoryCard from '../components/category/CategoryCard';
import CategoryCardSkeleton from '../components/ui/skeletons/CategoryCardSkeleton.jsx';
import SectionHeader from "../components/ui/SectionHeader.jsx";
import StaggerItem from "../components/animations/StaggerItem.jsx";
// Hooks
import {useCategories} from '../hooks/categories/useCategoryQueries';
import FeedbackState from "../components/ui/FeedbackState.jsx";
import {ShieldAlert} from "lucide-react";

const HomePage = () => {
  const {data: categories, isLoading, isFetching, isError} = useCategories();

  return (
    <div
      className={`flex w-full flex-col transition-opacity duration-300 ${isFetching && !isLoading ? 'opacity-60' : 'opacity-100'}`}>

      <div className="mb-8 shrink-0">
        <SectionHeader
          size="large"
          title="Elige tu"
          highlight="Temática"
          subtitle="Explora las diferentes categorías, vota por los mejores ítems y gana Kudos para escalar en el ranking mundial."
        />
      </div>
      {isError ? (<FeedbackState icon={ShieldAlert} title="Error"
                                 description="Servicio no disponible. Prueba de nuevo en unos minutos."
                                 iconColorClass="bg-red-500 text-white"/>) : (
        <StaggerGrid className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-10">
          {isLoading ? (
            Array.from({length: 9}).map((_, i) => <CategoryCardSkeleton key={i}/>)
          ) : (
            Array.isArray(categories) && categories.map((category) => (
              <StaggerItem key={category.id}>
                <CategoryCard category={category}/>
              </StaggerItem>
            ))
          )}
        </StaggerGrid>
      )}
    </div>
  );
};

export default HomePage;