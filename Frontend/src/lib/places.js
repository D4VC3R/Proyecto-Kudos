import { hasText } from './text.js';

// Zoom inicial del mapa de un destino (F4-03): un país se ve entero y una ciudad, de cerca.
export const PLACE_MAP_ZOOM = { country: 5, city: 11 };

const toCoordinate = (value) => (typeof value === 'number' || hasText(value) ? Number(value) : Number.NaN);

// Coordenadas { lat, lng } de attributes.coordinates (P625 de Wikidata, F4-02) si son válidas; si no, null.
export const getPlaceCoordinates = (attributes) => {
  const coordinates = attributes?.coordinates;
  if (!coordinates || typeof coordinates !== 'object' || Array.isArray(coordinates)) return null;
  const lat = toCoordinate(coordinates.lat);
  const lng = toCoordinate(coordinates.lng);
  if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return null;
  return { lat, lng };
};

// kind = 'país' → zoom de país; el resto (ciudad o sin kind) → zoom de ciudad.
export const getPlaceMapZoom = (attributes) => (attributes?.kind === 'país' ? PLACE_MAP_ZOOM.country : PLACE_MAP_ZOOM.city);
