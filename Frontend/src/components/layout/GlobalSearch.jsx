import React from 'react';
import { Search } from 'lucide-react';
import { twMerge } from 'tailwind-merge';
import StorageImage from '../ui/StorageImage.jsx';
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import { useGlobalSearch } from '../../hooks/common/useGlobalSearch.js';
import { getOptionId } from '../../lib/combobox.js';

const GlobalSearch = ({ className = '', onNavigate }) => {
  const { state, actions } = useGlobalSearch({ onNavigate });

  return (
    <form role="search" onSubmit={actions.handleSubmit} onBlur={actions.handleBlur} className={twMerge('relative w-full', className)}>
      <Search
        size={18}
        aria-hidden="true"
        className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
      />
      <input
        ref={state.inputRef}
        type="search"
        value={state.searchInput}
        onChange={actions.handleChange}
        onFocus={actions.handleFocus}
        onKeyDown={actions.handleKeyDown}
        placeholder="Buscar en Kudos..."
        role="combobox"
        aria-autocomplete="list"
        aria-expanded={state.isListboxOpen}
        aria-controls={state.isListboxOpen ? state.listboxId : undefined}
        aria-activedescendant={state.activeOptionId}
        aria-label="Buscar en Kudos"
        autoComplete="off"
        enterKeyHint="search"
        className="w-full rounded-2xl border border-border bg-background py-2 pl-10 pr-3 text-sm font-medium focus:bg-surface focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      {/* Región viva: anuncia "Buscando…", "Sin resultados" o el número de resultados al lector de pantalla (F0-21). */}
      <p role="status" aria-live="polite" aria-atomic="true" className="sr-only">{state.statusMessage}</p>

      {state.isOpen && (
        // El preventDefault evita que el input pierda el foco al hacer clic en el desplegable (el foco se queda en el combobox).
        <div
          className="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl bg-surface shadow-xl ring-1 ring-border"
          onMouseDown={(e) => e.preventDefault()}
        >
          {state.isLoading ? (
            <p className="px-4 py-3 text-sm font-medium text-text-normal">Buscando...</p>
          ) : state.isError ? (
            <p className="px-4 py-3 text-sm font-medium text-text-normal">No se ha podido buscar. Inténtalo de nuevo.</p>
          ) : state.results.length === 0 ? (
            <p className="px-4 py-3 text-sm font-medium text-text-normal">No hay resultados para «{state.term}».</p>
          ) : (
            <ul id={state.listboxId} role="listbox" aria-label="Resultados de la búsqueda">
              {state.results.map((item, index) => {
                const isActive = index === state.activeIndex;
                return (
                  <li
                    key={item.id}
                    id={getOptionId(state.listboxId, index)}
                    role="option"
                    aria-selected={isActive}
                    onClick={() => actions.handleResultClick(item)}
                    className={twMerge(
                      'flex w-full cursor-pointer items-center gap-3 px-4 py-2 text-left hover:bg-slate-50',
                      isActive && 'bg-slate-100'
                    )}
                  >
                    <div aria-hidden="true" className="h-12 w-12 shrink-0 overflow-hidden rounded-lg">
                      <StorageImage
                        src={item.images?.[0]?.variants?.thumb}
                        alt={item.name}
                        className="h-full w-full object-cover"
                        fallback={
                          <ItemPlaceholder
                            name={item.name}
                            categorySlug={item.category?.slug}
                            categoryIcon={item.category?.icon}
                            variant="thumb"
                            className="h-full w-full"
                          />
                        }
                      />
                    </div>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-bold text-text-highlight">{item.name}</p>
                      {item.category?.name && (
                        <p className="truncate text-xs font-medium text-text-normal">{item.category.name}</p>
                      )}
                    </div>
                  </li>
                );
              })}
            </ul>
          )}

          <button
            type="submit"
            onKeyDown={actions.handleSeeAllKeyDown}
            className="block w-full border-t border-border px-4 py-2.5 text-left text-sm font-bold text-primary hover:bg-slate-50 focus-visible:bg-slate-50 focus-visible:outline-none"
          >
            Ver todos los resultados
          </button>
        </div>
      )}
    </form>
  );
};

export default GlobalSearch;
