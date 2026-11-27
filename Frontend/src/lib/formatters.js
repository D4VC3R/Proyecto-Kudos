import {CATEGORY_ICONS_BY_NAME, CATEGORY_SLUG_ICONS, DEFAULT_CATEGORY_ICON} from "./constants.js";

export const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};

/**
 * Convierte una ruta relativa de la base de datos en una URL absoluta del Storage.
 * @param {string} path - La ruta de la imagen (ej: 'avatars/1.jpg')
 * @returns {string|null} - La URL completa o null si no hay ruta.
 */
export const getStorageUrl = (path) => {
  if (!path) return null;

  // Si por algún motivo la ruta ya es una URL completa (ej: una imagen externa), la respetamos
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path;
  }

  const baseUrl = import.meta.env.VITE_STORAGE_URL;

  // Limpiamos barras extra por si el baseUrl termina en '/' y el path empieza por '/'
  const cleanBaseUrl = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
  const cleanPath = path.startsWith('/') ? path : `/${path}`;

  return `${cleanBaseUrl}${cleanPath}`;
};

export const getScoreColor = (type, score) => {
  if (type === 'user') return 'bg-primary';
  if (score > 6) return 'bg-primary';
  if (score > 4) return 'bg-orange-500';
  return 'bg-red-600';
};

export const formatStatus = (status) => {
  switch (status.toLowerCase()) {
    case 'active':
      return 'ACTIVO';
    case 'inactive':
      return 'INACTIVO';
    case 'pending':
      return 'PENDIENTE';
    case 'rejected':
      return 'RECHAZADA';
    case 'accepted':
      return 'ACEPTADA';
    case 'changes_requested':
      return 'CAMBIOS SOLICITADOS';
    case 'resolved':
      return 'RESUELTO';
    case 'dismissed':
      return 'DESCARTADO';
    default: return status;
  }
}

// Icono de una categoría: primero `category.icon` (nombre lucide en kebab-case), después el slug y, si no, uno genérico.
export const resolveCategoryIcon = (category) =>
  CATEGORY_ICONS_BY_NAME[category?.icon] || CATEGORY_SLUG_ICONS[category?.slug] || DEFAULT_CATEGORY_ICON;



// "1 voto", "N votos"; sin recuento o con 0, "Sin votos".
export const formatVotes = (count) => {
  const n = Number(count) || 0;
  if (n <= 0) return 'Sin votos';
  return n === 1 ? '1 voto' : `${n} votos`;
};

// Nota media con un decimal, o null si no hay votos. Manda el recuento porque el backend admite la nota 0
// y una media de 0 no siempre significa "sin votos". Si la respuesta no trae el recuento, se usa la media.
export const formatScore = (avg, count) => {
  const value = Number(avg);
  if (avg == null || Number.isNaN(value)) return null;
  const hasVotes = count == null ? value > 0 : Number(count) > 0;
  return hasVotes ? value.toFixed(1) : null;
};
