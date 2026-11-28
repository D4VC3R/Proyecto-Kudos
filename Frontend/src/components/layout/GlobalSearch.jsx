import React from 'react';
import { Search } from 'lucide-react';
import { twMerge } from '../../lib/twMerge.js';
import StorageImage from '../ui/StorageImage.jsx';
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import { useGlobalSearch } from '../../hooks/common/useGlobalSearch.js';
import { getOptionId } from '../../lib/combobox.js';
import { FOCUS_RING } from './headerStyles.js';

const GlobalSearch = ({ className = '', onNavigate }) => {
  const { state, actions } = useGlobalSearch({ onNavigate });

  return (
    <form role="search" onSubmit={actions.handleSubmit} onBlur={actions.handleBlur} className={twMerge('relative w-full', className)}>
      <Search
        size={18}
        aria-hidden="true"
        className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted"
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
        className={`h-10 w-full rounded-sm border border-line-strong bg-surface pl-9 pr-3 text-small text-ink placeholder:text-ink-muted ${FOCUS_RING}`}
      />
      {/* Región viva: anuncia "Buscando…", "Sin resultados" o el número de resultados al lector de pantalla (F0-21). */}
      <p role="status" aria-live="polite" aria-atomic="true" className="sr-only">{state.statusMessage}</p>

      {state.isOpen && (
        // El preventDefault evita que el input pierda el foco al hacer clic en el desplegable (el foco se queda en el combobox).
        <div
          className="absolute left-0 right-0 top-full z-50 mt-2 rounded-md border border-line bg-surface p-1.5 shadow-raised"
          onMouseDown={(e) => e.preventDefault()}
        >
          {state.isLoading ? (
            <p className="px-2.5 py-2 text-small text-ink-muted">Buscando...</p>
          ) : state.isError ? (
            <p className="px-2.5 py-2 text-small text-ink-muted">No se ha podido buscar. Inténtalo de nuevo.</p>
          ) : state.results.length === 0 ? (
            <p className="px-2.5 py-2 text-small text-ink-muted">No hay resultados para «{state.term}».</p>
          ) : (
            <ul id={state.listboxId} role="listbox" aria-label="Resultados de la búsqueda" className="grid gap-0.5">
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
                      'flex w-full cursor-pointer items-center gap-3 rounded-sm px-2.5 py-2 text-left hover:bg-line/60',
                      isActive && 'bg-line/60 ring-2 ring-inset ring-accent'
                    )}
                  >
                    <div aria-hidden="true" className="h-12 w-8 shrink-0 overflow-hidden rounded-sm border border-line">
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
                      <p className="truncate text-small font-semibold text-ink">{item.name}</p>
                      {item.category?.name && (
                        <p className="truncate text-caption text-ink-muted">{item.category.name}</p>
                      )}
                    </div>
                  </li>
                );
              })}
            </ul>
          )}

          <div className="mt-1.5 border-t border-line pt-1.5">
            <button
              type="submit"
              onKeyDown={actions.handleSeeAllKeyDown}
              className={`block w-full rounded-sm p-2.5 text-left text-small font-semibold text-accent hover:bg-line/60 ${FOCUS_RING}`}
            >
              Ver todos los resultados
            </button>
          </div>
        </div>
      )}
    </form>
  );
};

export default GlobalSearch;
