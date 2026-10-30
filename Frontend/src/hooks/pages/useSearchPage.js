import { useMemo, useState } from 'react';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { useInfiniteItems } from '../items/useItemQueries.js';
import { useInfiniteScroll } from '../common/useInfiniteScroll.js';
import { useCategories } from '../categories/useCategoryQueries.js';
import { useCatalogSearch } from '../catalog/useCatalogQueries.js';
import { useImportCatalogItem } from '../catalog/useCatalogMutations.js';
import { selectIsAuthenticated, selectIsBanned, selectIsVerified, useSessionStore } from '../../store/useSessionStore.js';
import { buildAuthLinkState } from '../../lib/authRedirect.js';
import {
  buildItemUrl,
  getImportedItemId,
  groupItemsByCategory,
  MIN_LOCAL_RESULTS_FOR_CATALOG,
  normalizeSearchTerm
} from '../../lib/search.js';

/**
 * Hook personalizado para manejar la lógica de la página de resultados de búsqueda (`/buscar?q=…`).
 *
 * @return {object} Objeto con el estado, acciones y refs necesarios para la página.
 * - `state`: término buscado (`term`, `undefined` si no llega al mínimo), si había texto en la URL, carga, error,
 *   resultados agrupados por categoría (`groups`) y si se están cargando más.
 * - `state.catalog` (F1-07): importación bajo demanda. `isVisible` es true con pocos resultados locales;
 *   incluye el permiso (`canImport`, `isAuthenticated`, `isVerified`, `isBanned`), las categorías con proveedor
 *   y la elegida, los candidatos de la fuente externa (`results`), y el estado de la búsqueda y de la importación.
 * - `actions`: clic en un ítem (navega a su ficha) y las del bloque de catálogo: cambiar de categoría,
 *   buscar en la fuente externa, importar un candidato y abrir uno que ya está en Kudos.
 * - `refs`: referencia necesaria para implementar el scroll infinito.
 * */
export const useSearchPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const location = useLocation();

  const rawQuery = searchParams.get('q') ?? '';
  const term = normalizeSearchTerm(rawQuery);

  const {
    data,
    isLoading,
    isError,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isPlaceholderData
  } = useInfiniteItems(
    { search: term, per_page: 24 },
    { enabled: !!term }
  );

  const observerRef = useInfiniteScroll({ isFetchingNextPage, hasNextPage, fetchNextPage });

  const items = useMemo(() => data?.pages.flatMap((page) => page.data) || [], [data]);
  const groups = useMemo(() => groupItemsByCategory(items), [items]);

  const handleItemClick = (item) => {
    const url = buildItemUrl(item);
    if (url) navigate(url);
  };

  // Importación bajo demanda (F1-07).
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const isVerified = useSessionStore(selectIsVerified);
  const isBanned = useSessionStore(selectIsBanned);
  const { data: categories = [] } = useCategories();
  const catalogCategories = categories.filter((category) => category.has_catalog_provider);

  const [selectedCategory, setSelectedCategory] = useState('');
  const catalogCategory = selectedCategory || catalogCategories[0]?.slug || '';

  // Búsqueda externa lanzada a mano. Si cambia el término, se olvida (sin useEffect).
  const [submittedSearch, setSubmittedSearch] = useState(null);
  const activeSearch = submittedSearch?.q === term ? submittedSearch : null;

  const canImport = isAuthenticated && isVerified && !isBanned;
  const catalogQuery = useCatalogSearch(activeSearch ?? {}, { enabled: canImport && !!activeSearch });
  const importMutation = useImportCatalogItem();

  const isCatalogVisible = !!term && !isLoading && !isPlaceholderData && !isError && !hasNextPage
    && items.length < MIN_LOCAL_RESULTS_FOR_CATALOG && catalogCategories.length > 0;

  const handleCatalogCategoryChange = (event) => {
    setSelectedCategory(event.target.value);
    setSubmittedSearch(null);
  };

  const handleCatalogSearch = () => {
    setSubmittedSearch({ category: catalogCategory, q: term });
  };

  const handleCatalogImport = (result) => {
    importMutation.mutate(
      { category: activeSearch.category, externalId: result.external_id },
      {
        onSuccess: (response) => {
          const id = getImportedItemId(response);
          if (id) navigate(`/${activeSearch.category}/item/${id}`);
        }
      }
    );
  };

  const handleCatalogOpen = (result) => {
    navigate(`/${activeSearch.category}/item/${result.item_id}`);
  };

  return {
    state: {
      term,
      hasQuery: rawQuery.trim() !== '',
      isLoading: !!term && isLoading && !data,
      isError,
      groups,
      isFetchingNextPage,
      catalog: {
        isVisible: isCatalogVisible,
        isAuthenticated,
        isVerified,
        isBanned,
        canImport,
        loginLinkState: buildAuthLinkState(location),
        categories: catalogCategories,
        category: catalogCategory,
        hasSearched: !!activeSearch,
        results: catalogQuery.data ?? [],
        isSearching: catalogQuery.isFetching,
        errorMessage: catalogQuery.error?.message ?? null,
        importingExternalId: importMutation.isPending ? importMutation.variables?.externalId : null,
        isImporting: importMutation.isPending,
      },
    },
    actions: {
      handleItemClick,
      handleCatalogCategoryChange,
      handleCatalogSearch,
      handleCatalogImport,
      handleCatalogOpen,
    },
    refs: {
      observerRef,
    }
  };
};
