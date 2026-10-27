import { useState, useEffect } from 'react';
import { normalizeSearchTerm } from '../../lib/search.js';

/**
 * Hook personalizado para manejar filtros comunes en páginas de listado (paginación, búsqueda, ordenamiento, filtros adicionales).
 * @param initialSort - Valor inicial para el ordenamiento (ej. 'name|asc').
 * @param initialFilters - Objeto con los filtros iniciales (ej. { category: 'books', status: 'active' }).
 * @param debounceDelay - Tiempo en ms para debilitar la búsqueda, evitando llamadas excesivas al backend.
 * @param minSearchLength - Longitud mínima (tras recortar espacios) para que la búsqueda se envíe; por debajo,
 *   `debouncedSearch` es `undefined`. Por defecto 1 (endpoints de admin); `/api/items` exige 2 (usa MIN_SEARCH_LENGTH).
 *
 * @return {object} Objeto con el estado y funciones para manejar la paginación, búsqueda, ordenamiento y filtros adicionales.
 * - `debouncedSearch`: Valor de búsqueda listo para usar en consultas, actualizado después del debounce.
 * - `page` y `setPage`: Estado y función para manejar la página actual.
 * - `searchInput`, `setSearchInput` y `handleSearchChange`: Estado y funciones para manejar el input de búsqueda en tiempo real.
 * - `sortValue`, `handleSortChange`, `sortBy`, `sortOrder`: Estado y funciones para manejar el ordenamiento.
 * - `filters` y `handleFilterChange`: Estado y función para manejar filtros adicionales (categorías, estado, etc.).
 * */
export const useFilters = ({ initialSort = '', initialFilters = {}, debounceDelay = 400, minSearchLength = 1 } = {}) => {
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
        debouncedSearch: normalizeSearchTerm(debouncedSearch, minSearchLength),
        page, setPage,
        searchInput, setSearchInput, handleSearchChange,
        sortValue, handleSortChange, sortBy, sortOrder,
        filters, handleFilterChange, setFilters
    };
};