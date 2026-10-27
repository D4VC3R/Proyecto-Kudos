import { useId, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { keepPreviousData } from '@tanstack/react-query';
import { useFilters } from './useFilters.js';
import { useItems } from '../items/useItemQueries.js';
import { MIN_SEARCH_LENGTH, buildItemUrl, buildSearchUrl, normalizeSearchTerm } from '../../lib/search.js';
import { NO_ACTIVE_OPTION, getActiveOption, getNextActiveIndex, getOptionId } from '../../lib/combobox.js';

// Número de resultados que se muestran en la vista previa del desplegable.
const PREVIEW_SIZE = 6;

/**
 * Hook del buscador global de la cabecera: vista previa con debounce y navegación a `/buscar`.
 *
 * @param onNavigate - Función opcional que se ejecuta tras navegar (p. ej. cerrar el menú móvil).
 *
 * @return {object} Objeto con el estado y las acciones del buscador.
 * - `state`: texto del input, término buscado (tras el debounce), si el desplegable está abierto, si el listbox existe
 *   (hay resultados) y su `id`, la opción activa (índice y `id`), resultados, carga y error.
 * - `actions`: manejadores del input (cambio, foco, teclado: ↑/↓, Enter y Escape según el patrón combobox de ARIA),
 *   del desenfoque (va en el formulario, no en el input), del envío (Enter) y del clic en un resultado.
 * */
export const useGlobalSearch = ({ onNavigate } = {}) => {
  const navigate = useNavigate();
  const [isOpen, setIsOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(NO_ACTIVE_OPTION);
  const baseId = useId();
  const listboxId = `${baseId}-results`;

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

  const isDropdownOpen = isOpen && !!debouncedSearch;
  const isLoading = isFetching && results.length === 0;
  // El listbox solo existe cuando hay resultados que mostrar (no en carga, error ni vacío).
  const isListboxOpen = isDropdownOpen && !isLoading && !isError && results.length > 0;
  // Con keepPreviousData la lista puede encoger: un índice fuera de rango cuenta como "ninguna".
  const currentActiveIndex = isListboxOpen && activeIndex < results.length ? activeIndex : NO_ACTIVE_OPTION;

  const close = () => {
    setIsOpen(false);
    setActiveIndex(NO_ACTIVE_OPTION);
  };

  const handleChange = (e) => {
    handleSearchChange(e);
    setActiveIndex(NO_ACTIVE_OPTION);
    // Con el campo vacío (p. ej. Escape nativo de type="search") no se reabre con el término anterior del debounce.
    setIsOpen(e.target.value.trim() !== '');
  };

  const handleFocus = () => setIsOpen(true);

  // Solo cierra si el foco sale del buscador (no al pasar del input a "Ver todos los resultados").
  const handleBlur = (e) => {
    if (e.currentTarget.contains(e.relatedTarget)) return;
    close();
  };

  const handleKeyDown = (e) => {
    // Mientras se compone con un IME, las teclas son suyas.
    if (e.nativeEvent.isComposing) return;
    const { key } = e;

    if (key === 'ArrowDown' || key === 'ArrowUp') {
      if (!debouncedSearch) return;
      e.preventDefault(); // que la flecha no mueva el cursor del input
      setIsOpen(true);
      setActiveIndex((prev) => getNextActiveIndex(key, prev, results.length));
      return;
    }

    if (key === 'Enter') {
      const option = getActiveOption(results, currentActiveIndex);
      if (option) {
        e.preventDefault(); // evita el envío del formulario (que iría a /buscar)
        handleResultClick(option);
      }
      return; // sin opción activa, el submit del form navega a /buscar
    }

    if (key === 'Escape' && isDropdownOpen) {
      e.preventDefault(); // en type="search" el navegador vaciaría además el campo
      close();
    }
  };

  // Enter: usa el texto actual, sin esperar al debounce.
  const handleSubmit = (e) => {
    e.preventDefault();
    const term = normalizeSearchTerm(searchInput);
    if (!term) return;
    close();
    navigate(buildSearchUrl(term));
    onNavigate?.();
  };

  const handleResultClick = (item) => {
    const url = buildItemUrl(item);
    if (!url) return;
    close();
    setSearchInput('');
    navigate(url);
    onNavigate?.();
  };

  return {
    state: {
      searchInput,
      term: debouncedSearch,
      isOpen: isDropdownOpen,
      isListboxOpen,
      listboxId,
      activeIndex: currentActiveIndex,
      activeOptionId: currentActiveIndex === NO_ACTIVE_OPTION ? undefined : getOptionId(listboxId, currentActiveIndex),
      results,
      isLoading,
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
