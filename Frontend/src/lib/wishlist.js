// Agrupación de la wishlist por fecha de lanzamiento (F2-05).

// Fecha local de hoy en formato YYYY-MM-DD, comparable como texto con `released_at`.
export const getTodayDateString = (date = new Date()) => {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${date.getFullYear()}-${month}-${day}`;
};

// Sin fecha o con fecha de hoy o anterior: ya disponible.
export const isReleased = (releasedAt, today = getTodayDateString()) => !releasedAt || releasedAt <= today;

// Separa los votos `want` en "Ya disponible" (orden de alta, el de la API) y "Próximamente" (por fecha de lanzamiento, el más cercano primero).
export const splitWishlistByRelease = (votes = [], today = getTodayDateString()) => {
  const available = [];
  const upcoming = [];
  for (const vote of votes) {
    (isReleased(vote?.item?.released_at, today) ? available : upcoming).push(vote);
  }
  upcoming.sort((a, b) => a.item.released_at.localeCompare(b.item.released_at));
  return { available, upcoming };
};
