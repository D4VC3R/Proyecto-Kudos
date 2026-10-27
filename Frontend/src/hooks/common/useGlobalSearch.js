import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { keepPreviousData } from '@tanstack/react-query';
import { useFilters } from './useFilters.js';
import { useItems } from '../items/useItemQueries.js';
import { MIN_SEARCH_LENGTH, buildItemUrl, buildSearchUrl, normalizeSearchTerm } from '../../lib/search.js';

// Número de resultados que se muestran en la vista previa del desplegable.
const PREVIEW_SIZE = 6;

/**
 * Hook del buscador global de la cabecera: vista previa con debounce y navegación a `/buscar`.
 *
 * @param onNavigate - Función opcional que se ejecuta tras navegar (p. ej. cerrar el menú móvil).
 *
 * @return {object} Objeto con el estado y las acciones del buscador.
 * - `state`: texto del input, término buscado (tras el debounce), si el desplegable está abierto, resultados, carga y error.
 * - `actions`: manejadores del input (cambio, foco, desenfoque, teclado), del envío (Enter) y del clic en un resultado.
 * */
export const useGlobalSearch = ({ onNavigate } = {}) => {
  const navigate = useNavigate();
  const [isOpen, setIsOpen] = useState(false);

  const {
    searchInput,
    setSearchInput,
    handleSearchChange,
    debouncedSearch,
  } = useFilters({ debounceDelay: 300, minSearchLength: MIN_SEARCH_LENGTH });

  const { data: results = [], isFetching, isError } = useItems(
    { search: debouncedSearch, per_page: PREVIEW_SIZE },
    {
      enabled: !!debouncedSearch,
      select: (response) => response.data || [],
      placeholderData: keepPreviousData,
      staleTime: 1000 * 60,
    }
  );

  const handleChange = (e) => {
    handleSearchChange(e);
    setIsOpen(true);
  };

  const handleFocus = () => setIsOpen(true);

  const handleBlur = () => setIsOpen(false);

  const handleKeyDown = (e) => {
    if (e.key === 'Escape') setIsOpen(false);
  };

  // Enter: usa el texto actual, sin esperar al debounce.
  const handleSubmit = (e) => {
    e.preventDefault();
    const term = normalizeSearchTerm(searchInput);
    if (!term) return;
    setIsOpen(false);
    navigate(buildSearchUrl(term));
    onNavigate?.();
  };

  const handleResultClick = (item) => {
    const url = buildItemUrl(item);
    if (!url) return;
    setIsOpen(false);
    setSearchInput('');
    navigate(url);
    onNavigate?.();
  };

  return {
    state: {
      searchInput,
      term: debouncedSearch,
      isOpen: isOpen && !!debouncedSearch,
      results,
      isLoading: isFetching && results.length === 0,
      isError,
    },
    actions: {
      handleChange,
      handleFocus,
      handleBlur,
      handleKeyDown,
      handleSubmit,
      handleResultClick,
    },
  };
};
