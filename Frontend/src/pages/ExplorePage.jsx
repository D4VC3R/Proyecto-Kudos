import React, {useState, useEffect, useMemo} from 'react';
import {useNavigate, useParams} from 'react-router-dom';
import {Compass, Telescope, Flame} from 'lucide-react';
import {StaggerGrid} from "../components/animations/StaggerGrid.jsx";
import {StaggerItem} from "../components/animations/StaggerItem.jsx";

import {SectionHeader} from '../components/common/SectionHeader';
import {SearchFilter} from '../components/common/SearchFilter';
import {SelectFilter} from '../components/common/SelectFilter';
import {FeedbackState} from '../components/common/FeedbackState';
import {ItemCard} from '../components/items/ItemCard';

import {useInfiniteItems} from '../hooks/items/useItemQueries';
import {useCategoryDetail} from '../hooks/categories/useCategoryQueries';
import {useInfiniteScroll} from '../hooks/useInfiniteScroll';

const ExplorePage = () => {
  const {categorySlug} = useParams();

  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [sortValue, setSortValue] = useState('');
  const navigate = useNavigate();
  const {data: category, isLoading: isCategoryLoading} = useCategoryDetail(categorySlug);

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchInput);
    }, 400);

    return () => clearTimeout(timer);
  }, [searchInput]);

  const [sortBy, sortOrder] = sortValue ? sortValue.split('|') : ['vote_avg', 'desc'];

  const {
    data,
    isLoading: isItemsLoading,
    isError,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage
  } = useInfiniteItems(
    {
      category_id: category?.id,
      search: debouncedSearch,
      sort_by: sortBy,
      sort_order: sortOrder,
      per_page: 20
    },
    {
      enabled: !!category?.id
    }
  );

  const observerRef = useInfiniteScroll({
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage
  });

  const handleSearch = (e) => setSearchInput(e.target.value);
  const handleSortChange = (e) => setSortValue(e.target.value);
  const handleItemClick = (item) => {navigate(`/${categorySlug}/item/${item.id}`);};

  const flattenedItems = useMemo(() => {
    return data?.pages.flatMap((page) => page.data) || [];
  }, [data]);

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative container mx-auto px-4 py-8 max-w-6xl">
      <SectionHeader title="Explorar" highlight={category?.name || "Categoría"} icon={Compass}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={Flame}
            value={sortValue}
            onChange={handleSortChange}
            defaultOption="Mejor valorados"
            options={[
              {value: 'recent|desc', label: 'Más recientes'},
              {value: 'recent|asc', label: 'Más antiguos'},
              {value: 'vote_avg|asc', label: 'Peor valorados'}
            ]}
          />
          <SearchFilter
            value={searchInput}
            onChange={handleSearch}
            placeholder="Buscar ítem..."
          />
        </div>
      </SectionHeader>


      <div className="flex flex-col gap-8 w-full mt-4">
        {isCategoryLoading || (isItemsLoading && !data) ? (
          <FeedbackState icon={Telescope} isLoading title="Explorando el universo..."/>
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
              {flattenedItems.map((item, index) => (
                <StaggerItem key={item.id} alternate index={index}>
                  <ItemCard
                    key={item.id}
                    onClick={() => handleItemClick(item)}
                    item={item}
                  />
                </StaggerItem>
              ))}
            </StaggerGrid>
          </div>
        )}

        {isFetchingNextPage && (
          <div className="py-4">
            <FeedbackState icon={Telescope} isLoading title="Cargando más resultados..."/>
          </div>
        )}
        <div ref={observerRef} className="h-4 w-full"/>
      </div>
    </div>
  );
};

export default ExplorePage;