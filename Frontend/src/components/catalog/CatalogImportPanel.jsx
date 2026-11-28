import React from 'react';
import { Link } from 'react-router-dom';
import { ExternalLink, Plus, Search } from 'lucide-react';
import Button from '../ui/Button.jsx';
import Card from '../ui/Card.jsx';
import Select from '../ui/Select.jsx';

// Fuente externa de cada categoría importable, para nombrarla en los textos (la de videojuegos no es TMDB).
const SOURCE_NAMES = { peliculas: 'TMDB', series: 'TMDB', videojuegos: 'IGDB' };

// Panel de /buscar para añadir a Kudos un título que aún no está (F1-07): busca en la fuente externa (TMDB o IGDB)
// y permite importar un candidato. Solo presenta: la lógica está en useSearchPage. Los resultados son solo texto.
const CatalogImportPanel = ({ catalog, term, actions }) => {
  const { results } = catalog;
  const source = SOURCE_NAMES[catalog.category] ?? 'el catálogo externo';
  const noResults = catalog.hasSearched && !catalog.isSearching && !catalog.errorMessage && results.length === 0;

  return (
    <Card as="section" aria-labelledby="catalog-import-title" className="flex flex-col gap-4 p-5">
      <h2 id="catalog-import-title" className="font-display text-h2 text-ink">
        ¿No lo encuentras? Búscalo en {source}
      </h2>

      {!catalog.isAuthenticated ? (
        <div className="flex flex-col items-start gap-3">
          <p className="text-small text-ink-muted">Busca el título en el catálogo externo y añádelo a Kudos.</p>
          <Button as={Link} to="/login" state={catalog.loginLinkState} variant="primary" className="whitespace-nowrap">
            Inicia sesión para añadir
          </Button>
        </div>
      ) : !catalog.isVerified ? (
        <p className="text-small text-ink-muted">Verifica tu correo para añadir títulos a Kudos.</p>
      ) : catalog.isBanned ? (
        <p className="text-small text-ink-muted">Tu cuenta no puede añadir títulos.</p>
      ) : (
        <>
          <div className="flex flex-wrap items-end gap-3">
            <Select
              id="catalog-category"
              label="Categoría"
              value={catalog.category}
              onChange={actions.handleCatalogCategoryChange}
              options={catalog.categories.map((category) => ({ value: category.slug, label: category.name }))}
              className="w-full sm:w-56"
            />
            <Button variant="primary" icon={Search} isLoading={catalog.isSearching} onClick={actions.handleCatalogSearch}>
              Buscar en {source}
            </Button>
          </div>

          <div aria-live="polite" className="flex flex-col gap-2">
            {catalog.errorMessage && <p className="text-small text-danger">{catalog.errorMessage}</p>}
            {noResults && <p className="text-small text-ink-muted">No hay resultados en {source} para «{term}».</p>}
            {results.length > 0 && (
              <ul className="flex flex-col divide-y divide-line">
                {results.map((result) => (
                  <li key={result.external_id} className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex min-w-0 flex-col gap-1">
                      <span className="font-semibold text-ink">{result.name}</span>
                      <span className="text-small text-ink-muted">
                        {[result.year, result.original_title && result.original_title !== result.name ? result.original_title : null]
                          .filter(Boolean)
                          .join(' · ')}
                      </span>
                      {result.description && (
                        <p className="line-clamp-2 text-small text-ink-muted">{result.description}</p>
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
                        <span className="text-small font-semibold text-ink-muted">No disponible</span>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </>
      )}
    </Card>
  );
};

export default CatalogImportPanel;
