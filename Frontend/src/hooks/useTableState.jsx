import { useState, useCallback } from 'react';

export const useTableState = (initialPerPage = 15, defaultSortBy = 'created_at') => {
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(initialPerPage);
    const [search, setSearch] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const [sortBy, setSortBy] = useState(defaultSortBy);
    const [sortDirection, setSortDirection] = useState('desc');

    const goToPage = useCallback((newPage) => setPage(newPage), []);

    const updatePerPage = useCallback((value) => {
        setPerPage(Number(value) || initialPerPage);
        setPage(1);
    }, [initialPerPage]);

    const updateSearchInput = useCallback((value) => setSearchInput(value), []);

    const applySearch = useCallback(() => {
        setSearch(searchInput.trim());
        setPage(1);
    }, [searchInput]);

    const requestSort = useCallback((field) => {
        setSortBy((prevField) => {
            if (prevField === field) {
                setSortDirection((prevDir) => (prevDir === 'desc' ? 'asc' : 'desc'));
            } else {
                setSortDirection('desc');
            }
            return field;
        });
        setPage(1);
    }, []);

    const resetTableState = useCallback(() => {
        setPage(1);
        setSearch('');
        setSearchInput('');
        setSortBy(defaultSortBy);
        setSortDirection('desc');
    }, [defaultSortBy]);

    return {
        page,
        perPage,
        search,
        searchInput,
        sortBy,
        sortDirection,
        goToPage,
        updatePerPage,
        updateSearchInput,
        applySearch,
        requestSort,
        resetTableState,
    };
};