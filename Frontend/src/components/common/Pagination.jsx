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
                        onClick={handleDelegation}
                    >
            <span
                data-page={current_page - 1}
                className={`accion-paginacion relative inline-flex cursor-pointer items-center rounded-l-md px-2 py-2 text-slate-500 ring-1 ring-inset ring-slate-300 transition-colors bg-white ${
                    current_page === 1 ? 'pointer-events-none opacity-50 bg-slate-50' : 'hover:bg-slate-50 hover:text-slate-900'
                }`}
            >
              <span className="sr-only">Anterior</span>
                &larr; Ant
            </span>

                        <span
                            className="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 bg-white">
              Página {current_page} de {last_page}
            </span>

                        <span
                            data-page={current_page + 1}
                            className={`accion-paginacion relative inline-flex cursor-pointer items-center rounded-r-md px-2 py-2 text-slate-500 ring-1 ring-inset ring-slate-300 transition-colors bg-white ${
                                current_page === last_page ? 'pointer-events-none opacity-50 bg-slate-50' : 'hover:bg-slate-50 hover:text-slate-900'
                            }`}
                        >
              <span className="sr-only">Siguiente</span>
              Sig &rarr;
            </span>
                    </nav>
                </div>
            </div>
            {/* Opcional: versión mobile */}
            <div className="flex flex-1 justify-between sm:hidden">
              <span
                data-page={current_page - 1}
                className={`accion-paginacion relative inline-flex cursor-pointer items-center rounded-md px-4 py-2 ring-1 ring-inset ring-slate-300 bg-white text-sm font-medium text-slate-700 transition-colors ${
                  current_page === 1 ? 'pointer-events-none opacity-50' : 'hover:bg-slate-50'
                }`}
                onClick={handleDelegation}
              >
                Anterior
              </span>
              <span
                data-page={current_page + 1}
                className={`accion-paginacion relative ml-3 inline-flex cursor-pointer items-center rounded-md px-4 py-2 ring-1 ring-inset ring-slate-300 bg-white text-sm font-medium text-slate-700 transition-colors ${
                  current_page === last_page ? 'pointer-events-none opacity-50' : 'hover:bg-slate-50'
                }`}
                onClick={handleDelegation}
              >
                Siguiente
              </span>
            </div>
        </div>
    );
};