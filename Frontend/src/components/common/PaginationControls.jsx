export const PaginationControls = ({
  currentPage,
  lastPage,
  total,
  entityLabel,
  isFetching,
  canGoPrev,
  canGoNext,
  onFirst,
  onPrev,
  onNext,
  onLast,
  showUpdatingInSummary = true,
  extraActions = null,
}) => {
  return (
    <div className="flex flex-wrap items-center justify-between gap-2">
      <p className="text-xs text-slate-400">
        Pagina {currentPage} de {lastPage} · {total} {entityLabel}
        {showUpdatingInSummary && isFetching ? ' · Actualizando...' : ''}
      </p>

      <div className="flex flex-wrap items-center gap-2">
        <button
          className="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
          disabled={!canGoPrev || isFetching}
          onClick={onFirst}
          type="button"
        >
          Primera
        </button>
        <button
          className="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
          disabled={!canGoPrev || isFetching}
          onClick={onPrev}
          type="button"
        >
          Anterior
        </button>
        <button
          className="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
          disabled={!canGoNext || isFetching}
          onClick={onNext}
          type="button"
        >
          Siguiente
        </button>
        <button
          className="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
          disabled={!canGoNext || isFetching}
          onClick={onLast}
          type="button"
        >
          Ultima
        </button>

        {extraActions}
      </div>
    </div>
  );
};

