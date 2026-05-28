import { useState, useEffect } from 'react';

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