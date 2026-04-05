const apiErrorMap = {
  unauthenticated: 'Tu sesion expiro. Inicia sesion de nuevo.',
  forbidden: 'No tienes permisos para realizar esta accion.',
  validation_error: 'Revisa los campos del formulario.',
  conflict: 'No se pudo completar la accion por un conflicto de estado.',
  not_found: 'No se encontro el recurso solicitado.',
  route_not_found: 'La ruta solicitada no existe.',
  too_many_requests: 'Demasiados intentos. Espera un momento e intentalo otra vez.',
};

export const getApiErrorCode = (error) => {
  return error?.response?.data?.error?.code ?? null;
};

export const getApiErrorMessage = (error) => {
  const code = getApiErrorCode(error);
  const backendMessage = error?.response?.data?.error?.message;

  if (backendMessage) return backendMessage;
  if (code && apiErrorMap[code]) return apiErrorMap[code];
  return 'Ocurrio un error inesperado. Intentalo nuevamente.';
};

export const getApiValidationDetails = (error) => {
  return error?.response?.data?.error?.details ?? {};
};
