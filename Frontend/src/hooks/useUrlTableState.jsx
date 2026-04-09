import { useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';

export const useUrlTableState = (initialPerPage = 15, defaultSortBy = 'created_at') => {
    const [searchParams, setSearchParams] = useSearchParams();

    // 1. Extraer estado desde la URL
    const page = Math.max(1, Number(searchParams.get('page')) || 1);
    const perPage = Number(searchParams.get('per_page')) || initialPerPage;
    const search = searchParams.get('search') || '';
    const sortBy = searchParams.get('sort_by') || defaultSortBy;
    const sortDirection = searchParams.get('sort_dir') === 'asc' ? 'asc' : 'desc';

    // Función base para actualizar parámetros en lote y limpiar los valores por defecto
    const updateParams = useCallback((updates) => {
        setSearchParams((prev) => {
            const params = new URLSearchParams(prev);

            Object.entries(updates).forEach(([key, value]) => {
                if (value === null || value === '' || (key === 'page' && value === 1)) {
                    params.delete(key);
                } else {
                    params.set(key, String(value));
                }
            });

            return params;
        }, { replace: true });
    }, [setSearchParams]);

    const goToPage = useCallback((newPage) => {
        updateParams({ page: newPage });
    }, [updateParams]);

    const updatePerPage = useCallback((newPerPage) => {
        updateParams({ per_page: newPerPage, page: 1 });
    }, [updateParams]);

    // Recibe el término directamente del componente UI (que ya viene con debounce)
    const applySearch = useCallback((searchTerm) => {
        updateParams({ search: searchTerm.trim(), page: 1 });
    }, [updateParams]);

    const requestSort = useCallback((field) => {
        const isSameField = sortBy === field;
        const newDirection = isSameField && sortDirection === 'desc' ? 'asc' : 'desc';

        updateParams({
            sort_by: field,
            sort_dir: newDirection,
            page: 1
        });
    }, [sortBy, sortDirection, updateParams]);

    const resetTableState = useCallback(() => {
        updateParams({
            page: null,
            per_page: null,
            search: null,
            sort_by: null,
            sort_dir: null
        });
    }, [updateParams]);

    return {
        // Datos formateados listos para inyectar en React Query / Axios
        apiFilters: {
            page,
            per_page: perPage,
            search,
            sort_by: sortBy,
            sort_order: sortDirection
        },
        // Datos para la UI
        page,
        perPage,
        search,
        sortBy,
        sortDirection,
        // Acciones
        goToPage,
        updatePerPage,
        applySearch,
        requestSort,
        resetTableState,
        updateParams
    };
};