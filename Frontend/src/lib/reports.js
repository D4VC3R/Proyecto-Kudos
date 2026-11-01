// Reportes de contenido (F3-04, DSA). Las etiquetas coinciden con Report::REASON_LABELS del backend.
export const REPORT_REASONS = [
  { value: 'spam', label: 'Spam o publicidad' },
  { value: 'odio', label: 'Discurso de odio' },
  { value: 'acoso', label: 'Acoso' },
  { value: 'ilegal', label: 'Contenido ilegal' },
  { value: 'spoiler', label: 'Spoiler sin avisar' },
  { value: 'otro', label: 'Otro motivo' },
];

export const REPORT_DETAILS_MAX_LENGTH = 1000;

// Filtros de la cola de reportes del admin.
export const REPORT_STATUS_OPTIONS = [
  { value: 'pending', label: 'Pendientes' },
  { value: 'resolved', label: 'Resueltos' },
  { value: 'dismissed', label: 'Descartados' },
];

export const REPORTABLE_TYPE_OPTIONS = [
  { value: 'comment', label: 'Comentarios' },
  { value: 'list', label: 'Listas' },
  { value: 'user', label: 'Perfiles' },
];

export const REPORTABLE_TYPE_LABELS = {
  comment: 'Comentario',
  list: 'Lista',
  user: 'Perfil',
};
