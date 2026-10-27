// Indica si un valor es un texto con contenido: no nulo, string y no vacío tras trim().
export const hasText = (value) => typeof value === 'string' && value.trim() !== '';
