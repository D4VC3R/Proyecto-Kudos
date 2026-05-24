import React, { useMemo } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Compass, Telescope, Flame } from 'lucide-react';
import { StaggerGrid } from "../components/animations/StaggerGrid.jsx";
import { StaggerItem } from "../components/animations/StaggerItem.jsx";
import { SectionHeader } from '../components/common/SectionHeader';
import { SearchFilter } from '../components/common/SearchFilter';
import { SelectFilter } from '../components/common/SelectFilter';
import { FeedbackState } from '../components/common/FeedbackState';
import { ItemCard } from '../components/items/ItemCard';
import { useInfiniteItems } from '../hooks/items/useItemQueries';
import { useInfiniteScroll } from '../hooks/common/useInfiniteScroll';
import { useFilters } from '../hooks/common/useFilters';

const ExplorePage = () => {
    const { categorySlug } = useParams();
    const navigate = useNavigate();

    // Controlador de filtros
    const {
        searchInput,
        debouncedSearch,
        handleSearchChange,
        sortValue,
        handleSortChange,
        sortBy,
        sortOrder,
    } = useFilters({ initialSort: 'vote_avg|desc' });

    // Consumo de API
    const {
        data,
        isLoading: isItemsLoading,
        isError,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage
    } = useInfiniteItems(
        {
            category_slug: categorySlug,
            search: debouncedSearch,
            sort_by: sortBy,
            sort_order: sortOrder,
            per_page: 20
        },
        { enabled: !!categorySlug }
    );

    // Controladores de Scroll y clic
    const observerRef = useInfiniteScroll({ isFetchingNextPage, hasNextPage, fetchNextPage });
    const handleItemClick = (item) => { navigate(`/${categorySlug}/item/${item.id}`); };

    // Lógica de renderizado de datos
    const flattenedItems = useMemo(() => {
        return data?.pages.flatMap((page) => page.data) || [];
    }, [data]);

    const displayCategoryName = useMemo(() => {
        if (flattenedItems.length > 0 && flattenedItems[0].category) {
            return flattenedItems[0].category.name;
        }
        return categorySlug.charAt(0).toUpperCase() + categorySlug.slice(1).replace(/-/g, ' ');
    }, [flattenedItems, categorySlug]);

    return (
        <div className="flex flex-col gap-6 animate-fade-in relative container mx-auto px-4 py-8 max-w-6xl">
            <SectionHeader title="Explorar" highlight={displayCategoryName} icon={Compass}>
                <div className="flex gap-2 flex-wrap">
                    <SelectFilter
                        icon={Flame}
                        value={sortValue}
                        onChange={handleSortChange}
                        defaultOption="Mejor valorados"
                        options={[
                            { value: 'recent|desc', label: 'Más recientes' },
                            { value: 'recent|asc', label: 'Más antiguos' },
                            { value: 'vote_avg|asc', label: 'Peor valorados' }
                        ]}
                    />
                    <SearchFilter
                        value={searchInput}
                        onChange={handleSearchChange}
                        placeholder="Buscar ítem..."
                    />
                </div>
            </SectionHeader>

            <div className="flex flex-col gap-8 w-full mt-4">
                {isItemsLoading && !data ? (
                    <FeedbackState icon={Telescope} isLoading title="Explorando el universo..." />
                ) : isError ? (
                    <FeedbackState
                        icon={Compass}
                        title="Problemas de conexión"
                        description="No pudimos cargar los items. Por favor, intenta nuevamente."
                        iconColorClass="bg-red-100 text-red-500"
                    />
                ) : flattenedItems.length === 0 ? (
                    <FeedbackState
                        icon={Telescope}
                        title="Ningún ítem en el horizonte"
                        description="No se encontraron resultados con los filtros actuales."
                    />
                ) : (
                    <div className="overflow-x-hidden w-full py-4">
                        <StaggerGrid className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 justify-center">
                            {flattenedItems.map((item, index) => {
                                return (
                                    <StaggerItem key={item.id} alternate index={index}>
                                        <ItemCard
                                            item={item}
                                            onClick={() => handleItemClick(item)}
                                        />
                                    </StaggerItem>
                                );
                            })}
                        </StaggerGrid>
                    </div>
                )}
                {isFetchingNextPage && (
                    <div className="py-4">
                        <FeedbackState icon={Telescope} isLoading title="Cargando más resultados..." />
                    </div>
                )}
                <div ref={observerRef} className="h-4 w-full" />
            </div>
        </div>
    );
};

export default ExplorePage;