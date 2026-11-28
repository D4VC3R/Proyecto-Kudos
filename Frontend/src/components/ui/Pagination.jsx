import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import Button from './Button.jsx';

const Pagination = ({ meta, onPageChange }) => {
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
    <nav aria-label="Paginación" data-pagination className="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
      <p className="hidden text-small text-ink-muted sm:block">
        Mostrando un total de <span className="font-semibold text-ink">{total}</span> resultados
      </p>
      <div className="flex w-full items-center justify-between gap-2 sm:w-auto">
        <Button variant="ghost" onClick={handlePrevious} disabled={current_page <= 1}>
          <ChevronLeft aria-hidden="true" size={18} className="shrink-0" />Anterior
        </Button>
        <span className="text-small font-semibold tabular-nums text-ink">Página {current_page} de {last_page}</span>
        <Button variant="ghost" onClick={handleNext} disabled={current_page >= last_page}>
          Siguiente<ChevronRight aria-hidden="true" size={18} className="shrink-0" />
        </Button>
      </div>
    </nav>
  );
};

export default Pagination;
