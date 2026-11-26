/** Error de la API que conserva el estado HTTP y el `code`/`details` del formato `{"error": {...}}` del backend.
 *  Extiende Error y mantiene `message`, así que quien solo lee `error.message` (los toasts) no cambia.
 *  Sin respuesta (red caída, CORS), `status` es 0. */
export class ApiError extends Error {
  constructor(message, {status = 0, code = null, details = null} = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

/** Reparte los errores por campo de un 422 (`details`: {campo: [mensajes]}) en un formulario de react-hook-form.
 *  Devuelve cuántos campos ha marcado; si no es un 422 con `details`, no hace nada. */
export const applyFieldErrors = (error, setError) => {
  if (!(error instanceof ApiError) || error.status !== 422) return 0;
  const details = error.details;
  if (!details || typeof details !== 'object' || Array.isArray(details)) return 0;
  let marked = 0;
  Object.entries(details).forEach(([field, messages]) => {
    const message = Array.isArray(messages) ? messages[0] : messages;
    if (typeof message !== 'string' || message === '') return;
    setError(field, {type: 'server', message});
    marked += 1;
  });
  return marked;
};
