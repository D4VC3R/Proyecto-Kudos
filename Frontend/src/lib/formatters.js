import {categoryIcons} from "./constants.js";
import {LayoutGrid} from "lucide-react";

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
    default: return status;
  }
}

// Devuelve el icono asociado a una categoría
export const getCategoryIcon = (categoryName) => {

  const normalizedName = categoryName
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");

  return categoryIcons[normalizedName] || LayoutGrid;
};


