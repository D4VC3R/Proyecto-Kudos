import { useState, useEffect } from 'react';

/**
 * Hook personalizado para manejar filtros comunes en páginas de listado (paginación, búsqueda, ordenamiento, filtros adicionales).
 * @param initialSort - Valor inicial para el ordenamiento (ej. 'name|asc').
 * @param initialFilters - Objeto con los filtros iniciales (ej. { category: 'books', status: 'active' }).
 * @param debounceDelay - Tiempo en ms para debilitar la búsqueda, evitando llamadas excesivas al backend.
 *
 * @return {object} Objeto con el estado y funciones para manejar la paginación, búsqueda, ordenamiento y filtros adicionales.
 * - `debouncedSearch`: Valor de búsqueda listo para usar en consultas, actualizado después del debounce.
 * - `page` y `setPage`: Estado y función para manejar la página actual.
 * - `searchInput` y `handleSearchChange`: Estado y función para manejar el input de búsqueda en tiempo real.
 * - `sortValue`, `handleSortChange`, `sortBy`, `sortOrder`: Estado y funciones para manejar el ordenamiento.
 * - `filters` y `handleFilterChange`: Estado y función para manejar filtros adicionales (categorías, estado, etc.).
 * */
export const useFilters = ({ initialSort = '', initialFilters = {}, debounceDelay = 400 } = {}) => {
    const [page, setPage] = useState(1);
    const [searchInput, setSearchInput] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [sortValue, setSortValue] = useState(initialSort);
    const [filters, setFilters] = useState(initialFilters);

    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchInput);
            setPage(1); // Al buscar, siempre volvemos a la página 1
        }, debounceDelay);

        return () => clearTimeout(timer);
    }, [searchInput, debounceDelay]);

    const handleSearchChange = (e) => setSearchInput(e.target.value);

    const handleSortChange = (e) => {
        setSortValue(e.target.value);
        setPage(1); // Volvemos a la pag 1 al reordenar
    };

    // Manejador genérico para cualquier Select (Categorías, Estado, etc.)
    const handleFilterChange = (filterKey, value) => {
        setFilters(prev => ({ ...prev, [filterKey]: value }));
        setPage(1);
    };

    const [sortBy, sortOrder] = sortValue ? sortValue.split('|') : [undefined, undefined];

    return {
        debouncedSearch: debouncedSearch || undefined,
        page, setPage,
        searchInput, handleSearchChange,
        sortValue, handleSortChange, sortBy, sortOrder,
        filters, handleFilterChange, setFilters
    };
};