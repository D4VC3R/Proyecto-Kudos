import React from 'react';

export const Pagination = ({meta, onPageChange}) => {
    if (!meta || meta.last_page <= 1) return null;

    const {current_page, last_page, total} = meta;

    // Delegación de eventos centralizada
    const handleDelegation = (e) => {
        // Buscamos si el clic provino de nuestro span custom (o un hijo dentro de él)
        const target = e.target.closest('.accion-paginacion');

        if (!target) return; // Si hicieron clic en otro lado del nav, ignoramos
        // Extraemos el valor del data-attribute
        const nextPage = Number(target.dataset.page);

        // Solo disparamos el cambio si la página es válida y distinta a la actual
        if (nextPage && nextPage !== current_page && nextPage >= 1 && nextPage <= last_page) {
            onPageChange(nextPage);
        }
    };

    return (
        <div className="flex items-center justify-between border-t border-slate-800 bg-slate-950 px-4 py-3 sm:px-6">
            <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p className="text-sm text-slate-400">
                        Mostrando un total de <span className="font-medium text-slate-200">{total}</span> resultados
                    </p>
                </div>

                <div>
                    <nav
                        className="isolate inline-flex -space-x-px rounded-md shadow-sm select-none"
                        aria-label="Pagination"
                        onClick={handleDelegation}
                    >
            <span
                data-page={current_page - 1}
                className={`accion-paginacion relative inline-flex cursor-pointer items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-800 transition-colors ${
                    current_page === 1 ? 'pointer-events-none opacity-50' : 'hover:bg-slate-800 hover:text-white'
                }`}
            >
              <span className="sr-only">Anterior</span>
                &larr; Ant
            </span>

                        <span
                            className="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-200 ring-1 ring-inset ring-slate-800">
              Página {current_page} de {last_page}
            </span>

                        <span
                            data-page={current_page + 1}
                            className={`accion-paginacion relative inline-flex cursor-pointer items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-800 transition-colors ${
                                current_page === last_page ? 'pointer-events-none opacity-50' : 'hover:bg-slate-800 hover:text-white'
                            }`}
                        >
              <span className="sr-only">Siguiente</span>
              Sig &rarr;
            </span>
                    </nav>
                </div>
            </div>
        </div>
    );
};