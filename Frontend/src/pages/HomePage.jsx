import React from 'react';
import { StaggeredGrid } from '../components/animations/StaggeredGrid';
import { CategoryCard } from '../components/category/CategoryCard';
import { useCategories } from '../hooks/categories/useCategoryQueries';
import { Loader2 } from 'lucide-react';

const HomePage = () => {
    const { data: categories, isLoading, isFetching, isError } = useCategories();

    if (isLoading) {
        return (
            <div className="flex justify-center items-center py-20 min-h-[50vh]">
                <Loader2 className="animate-spin text-blue-500" size={48} />
            </div>
        );
    }

    if (isError) {
        return (
            <div className="flex justify-center items-center py-20 min-h-[50vh]">
                <p className="text-red-500 font-medium">Hubo un error cargando las categorías.</p>
            </div>
        );
    }

    return (
        <div className={`mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 transition-opacity duration-300 ${isFetching ? 'opacity-60' : 'opacity-100'}`}>
            <div className="mb-12 text-center md:text-left">
                <h1 className="text-4xl font-black tracking-tight text-slate-900 sm:text-5xl md:text-6xl">
                    Elige tu <span className="text-blue-600 drop-shadow-sm">Temática</span>
                </h1>
                <p className="mt-4 text-lg font-medium text-slate-500 max-w-2xl">
                    Explora las diferentes categorías, vota por los mejores ítems y gana Kudos para subir en el ranking mundial.
                </p>
            </div>

            <StaggeredGrid className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-10">
                {Array.isArray(categories) && categories.map((category) => (
                    <CategoryCard key={category.id} category={category} />
                ))}
            </StaggeredGrid>
        </div>
    );
};

export default HomePage;