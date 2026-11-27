import React from 'react';
import { Link } from 'react-router-dom';
import { ExternalLink, Plus, Search } from 'lucide-react';
import Button from '../ui/Button.jsx';

// Panel de /buscar para añadir a Kudos un título que aún no está (F1-07): busca en la fuente externa (TMDB)
// y permite importar un candidato. Solo presenta: la lógica está en useSearchPage. Los resultados son solo texto.
const CatalogImportPanel = ({ catalog, term, actions }) => {
  const { results } = catalog;
  const noResults = catalog.hasSearched && !catalog.isSearching && !catalog.errorMessage && results.length === 0;

  return (
    <section aria-labelledby="catalog-import-title" className="flex flex-col gap-4 rounded-3xl border border-border bg-surface p-6">
      <h3 id="catalog-import-title" className="text-xl font-black tracking-tight text-text-highlight">
        ¿No lo encuentras? Búscalo en TMDB
      </h3>

      {!catalog.isAuthenticated ? (
        <div className="flex flex-col items-start gap-3">
          <p className="text-sm text-text-normal">Busca el título en el catálogo externo y añádelo a Kudos.</p>
          <Button as={Link} to="/login" state={catalog.loginLinkState} variant="primary" className="whitespace-nowrap">
            Inicia sesión para añadir
          </Button>
        </div>
      ) : !catalog.isVerified ? (
        <p className="text-sm text-text-normal">Verifica tu correo para añadir títulos a Kudos.</p>
      ) : catalog.isBanned ? (
        <p className="text-sm text-text-normal">Tu cuenta no puede añadir títulos.</p>
      ) : (
        <>
          <div className="flex flex-wrap items-end gap-3">
            <div className="flex flex-col gap-1">
              <label htmlFor="catalog-category" className="text-sm font-bold text-text-normal">Categoría</label>
              <select
                id="catalog-category"
                className="rounded-2xl border border-border-strong bg-surface py-2.5 px-4 pr-8 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 font-bold text-slate-700"
                value={catalog.category}
                onChange={actions.handleCatalogCategoryChange}
              >
                {catalog.categories.map((category) => (
                  <option key={category.slug} value={category.slug}>{category.name}</option>
                ))}
              </select>
            </div>
            <Button icon={Search} isLoading={catalog.isSearching} onClick={actions.handleCatalogSearch}>
              Buscar en TMDB
            </Button>
          </div>

          <div aria-live="polite" className="flex flex-col gap-2">
            {catalog.errorMessage && <p className="text-sm text-red-600">{catalog.errorMessage}</p>}
            {noResults && <p className="text-sm text-text-normal">TMDB no ha encontrado nada para «{term}».</p>}
            {results.length > 0 && (
              <ul className="flex flex-col divide-y divide-border">
                {results.map((result) => (
                  <li key={result.external_id} className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex min-w-0 flex-col gap-1">
                      <span className="font-bold text-text-highlight">{result.name}</span>
                      <span className="text-sm text-text-normal">
                        {[result.year, result.original_title && result.original_title !== result.name ? result.original_title : null]
                          .filter(Boolean)
                          .join(' · ')}
                      </span>
                      {result.description && (
                        <p className="line-clamp-2 text-sm text-text-normal">{result.description}</p>
                      )}
                    </div>
                    <div className="shrink-0">
                      {!result.exists ? (
                        <Button
                          icon={Plus}
                          isLoading={catalog.importingExternalId === result.external_id}
                          disabled={catalog.isImporting}
                          onClick={() => actions.handleCatalogImport(result)}
                        >
                          Añadir a Kudos
                        </Button>
                      ) : result.item_id ? (
                        <Button variant="ghost" icon={ExternalLink} onClick={() => actions.handleCatalogOpen(result)}>
                          Ver en Kudos
                        </Button>
                      ) : (
                        <span className="text-sm font-bold text-text-normal">No disponible</span>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </>
      )}
    </section>
  );
};

export default CatalogImportPanel;
