export const FIELD_LABELS = {
  'seasons': 'Temporadas',
  'release_year': 'Año de Lanzamiento',
  'genre': 'Género',
  'developer': 'Desarrollador',
  'director': 'Director',
  'platform': 'Plataforma',
  'author': 'Autor',
  'pages': 'Páginas',
  'episodes': 'Episodios',
  'studio': 'Estudio de Animación',
  'party': 'Partido'
};

export const formatKeyName = (key) => {
  if (FIELD_LABELS[key]) return FIELD_LABELS[key];
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

