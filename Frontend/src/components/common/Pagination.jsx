import React from 'react';
import { Button } from './Button';

export const Pagination = ({ meta, onPageChange }) => {
  // Guarda de seguridad temprana
  if (!meta || meta.last_page <= 1) return null;

  const { current_page, last_page, total } = meta;

  // Comprobaciones de límite del paginado.
  const handlePrevious = () => {
    if (current_page > 1) {
      onPageChange(current_page - 1);
    }
  };

  const handleNext = () => {
    if (current_page < last_page) {
      onPageChange(current_page + 1);
    }
  };

  return (
    <div className="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3 sm:px-6 mt-4 rounded-xl shadow-sm">

      <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
          <p className="text-sm text-slate-500">
            Mostrando un total de <span className="font-medium text-slate-900">{total}</span> resultados
          </p>
        </div>

        <div>
          <nav
            className="isolate inline-flex -space-x-px rounded-md shadow-sm select-none"
            aria-label="Pagination"
          >
            <Button
              type="button"
              onClick={handlePrevious}
              disabled={current_page <= 1}
              variant="outline"
              color="neutral"
              radius="md"
            >
              <span className="sr-only">Anterior</span>
              &larr; Ant
            </Button>

            <span className="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 bg-white">
              Página {current_page} de {last_page}
            </span>

            <Button
              type="button"
              onClick={handleNext}
              disabled={current_page >= last_page}
              variant="outline"
              color="neutral"
              radius="md"
              className="rounded-r-md rounded-l-none relative inline-flex items-center px-2 py-2 bg-white"
            >
              <span className="sr-only">Siguiente</span>
              Sig &rarr;
            </Button>
          </nav>
        </div>
      </div>

      <div className="flex flex-1 justify-between sm:hidden">
        <Button
          type="button"
          onClick={handlePrevious}
          disabled={current_page <= 1}
          variant="outline"
          color="neutral"
        >
          Anterior
        </Button>
        <Button
          type="button"
          onClick={handleNext}
          disabled={current_page >= last_page}
          variant="outline"
          color="neutral"
        >
          Siguiente
        </Button>
      </div>
    </div>
  );
};