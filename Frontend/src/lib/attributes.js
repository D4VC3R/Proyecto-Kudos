import { hasText } from './text.js';

// Duración en minutos → "2 h 16 min", "45 min" o "2 h" (attribute_schema tipo minutes, F1-09).
export const formatMinutes = (value) => {
  if (typeof value !== 'number' && !hasText(value)) return null;
  const total = Math.round(Number(value));
  if (!Number.isFinite(total) || total <= 0) return null;
  const hours = Math.floor(total / 60);
  const minutes = total % 60;
  if (hours === 0) return `${minutes} min`;
  if (minutes === 0) return `${hours} h`;
  return `${hours} h ${minutes} min`;
};

// Fecha "YYYY-MM-DD" → formato local español ("14 de marzo de 2024"). Se construye en hora local para que no cambie de día por la zona horaria.
export const formatAttributeDate = (value) => {
  if (!hasText(value)) return null;
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value.trim());
  if (!match) return null;
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  if (Number.isNaN(date.getTime())) return null;
  return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
};

const toText = (value) => {
  if (typeof value === 'number' && Number.isFinite(value)) return String(value);
  return hasText(value) ? value.trim() : null;
};

// Cada tipo devuelve la lista de textos que se pintan como chips. coordinates (mapa de la F4-03) y url no se pintan todavía.
const VALUE_FORMATTERS = {
  string: (value) => [toText(value)],
  integer: (value) => [toText(value)],
  minutes: (value) => [formatMinutes(value)],
  date: (value) => [formatAttributeDate(value)],
  tags: (value) => (Array.isArray(value) ? value.map(toText) : []),
};

// Atributos que hay que pintar, en el orden del attribute_schema: [{ key, label, type, values }].
// Se saltan los campos sin valor, con un tipo que no se pinta o, con cardOnly, sin show_in_card.
export const getAttributeEntries = (schema, attributes, { cardOnly = false } = {}) => {
  if (!Array.isArray(schema) || !attributes || typeof attributes !== 'object' || Array.isArray(attributes)) return [];

  return schema.flatMap((field) => {
    if (!field || typeof field !== 'object' || !hasText(field.key)) return [];
    if (cardOnly && !field.show_in_card) return [];

    const format = VALUE_FORMATTERS[field.type];
    if (!format || !Object.prototype.hasOwnProperty.call(attributes, field.key)) return [];

    const values = format(attributes[field.key]).filter(hasText);
    if (values.length === 0) return [];

    return [{ key: field.key, label: hasText(field.label) ? field.label.trim() : field.key, type: field.type, values }];
  });
};
