// Longitud mínima de una búsqueda: el backend valida `search` con min:2 (F0-09).
export const MIN_SEARCH_LENGTH = 2;

// Devuelve el término recortado si tiene al menos `minLength` caracteres; si no, undefined (no se envía).
// Cuenta puntos de código ([...term]) para coincidir con mb_strlen del backend.
export const normalizeSearchTerm = (value, minLength = MIN_SEARCH_LENGTH) => {
  if (typeof value !== 'string') return undefined;
  const term = value.trim();
  const length = [...term].length;
  return length > 0 && length >= minLength ? term : undefined;
};

// URL de la página de resultados.
export const buildSearchUrl = (term) => `/buscar?q=${encodeURIComponent(term)}`;

// URL de la ficha de un ítem; null si no trae categoría.
export const buildItemUrl = (item) => (item?.category?.slug ? `/${item.category.slug}/item/${item.id}` : null);

// Agrupa los ítems por categoría, en el orden en que aparece cada categoría por primera vez.
export const groupItemsByCategory = (items = []) => {
  const groups = new Map();
  for (const item of items) {
    const key = item?.category?.slug ?? '';
    if (!groups.has(key)) groups.set(key, { key, category: item?.category ?? null, items: [] });
    groups.get(key).items.push(item);
  }
  return [...groups.values()];
};

// Con menos resultados locales que este número, /buscar ofrece buscar en el catálogo externo (F1-07).
export const MIN_LOCAL_RESULTS_FOR_CATALOG = 5;

// Id del ítem tras POST /catalog/import: 201 devuelve la ficha (data.id); 200, { item_id }.
export const getImportedItemId = (response) => response?.data?.id ?? response?.data?.item_id ?? null;
