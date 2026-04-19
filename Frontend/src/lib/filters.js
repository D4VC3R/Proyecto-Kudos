/**
 * Combina un estado de filtros previo con nuevas actualizaciones.
 * Elimina automáticamente claves con valor null/'' y prescinde del 'page' local.
 *
 * @param {Object} prevFilters Estado actual de los filtros
 * @param {Object} updates Nuevos atributos o cambios a fusionar
 * @returns {Object} Objeto de filtros completamente limpio
 */
export const mergeFilters = (prevFilters, updates) => {
  const next = { ...prevFilters };

  Object.entries(updates).forEach(([key, value]) => {
    if (value === null || value === '') {
      delete next[key];
    } else {
      next[key] = value;
    }
  });

  if ('page' in next) {
    delete next.page;
  }

  return next;
};

