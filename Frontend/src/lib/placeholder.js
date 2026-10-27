// Parámetros del placeholder generativo: con S=60 % y L entre 20 y 28 % el texto blanco
// tiene un contraste >= 4,5:1 (WCAG AA) en cualquier tono.
export const PLACEHOLDER_SATURATION = 60;
export const PLACEHOLDER_LIGHTNESS = { from: 28, to: 20 };
export const PLACEHOLDER_HUE_SHIFT = 40;

// Hash FNV-1a de 32 bits: estable entre sesiones y navegadores.
export const hashString = (value) => {
  let hash = 0x811c9dc5;
  for (const char of String(value ?? '')) {
    hash ^= char.codePointAt(0);
    hash = Math.imul(hash, 0x01000193);
  }
  return hash >>> 0;
};

// Dos tonos HSL derivados del nombre (sin distinguir mayúsculas ni espacios exteriores).
export const getPlaceholderColors = (name) => {
  const hue = hashString(String(name ?? '').trim().toLocaleLowerCase('es-ES')) % 360;
  const hueTo = (hue + PLACEHOLDER_HUE_SHIFT) % 360;
  return {
    hue,
    from: `hsl(${hue}, ${PLACEHOLDER_SATURATION}%, ${PLACEHOLDER_LIGHTNESS.from}%)`,
    to: `hsl(${hueTo}, ${PLACEHOLDER_SATURATION}%, ${PLACEHOLDER_LIGHTNESS.to}%)`,
  };
};

// Hasta dos iniciales: primera letra o cifra de las dos primeras palabras.
export const getInitials = (name) => {
  if (!name) return '';
  return String(name)
    .trim()
    .split(/\s+/)
    .map((word) => word.replace(/^[^\p{L}\p{N}]+/u, ''))
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => Array.from(word)[0])
    .join('')
    .toLocaleUpperCase('es-ES');
};
