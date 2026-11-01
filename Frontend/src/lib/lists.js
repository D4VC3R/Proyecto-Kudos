// Listas de usuario (F3-01). Los límites de longitud coinciden con los del backend.
export const LIST_TITLE_MAX_LENGTH = 120;
export const LIST_DESCRIPTION_MAX_LENGTH = 2000;
export const LIST_NOTE_MAX_LENGTH = 280;

export const LIST_VISIBILITY_OPTIONS = [
  { value: 'private', label: 'Privada', description: 'Solo la ves tú.' },
  { value: 'unlisted', label: 'No listada', description: 'La ve quien tenga el enlace, pero no aparece en los listados.' },
  { value: 'public', label: 'Pública', description: 'Cualquiera puede verla.' },
];

export const LIST_VISIBILITY_LABELS = { private: 'Privada', unlisted: 'No listada', public: 'Pública' };
