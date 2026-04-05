import { getApiErrorCode, getApiErrorMessage, getApiValidationDetails } from '../lib/apiErrorMap';
import { useSessionStore } from '../store/useSessionStore';

export const useApiErrorHandler = () => {
  const clearSession = useSessionStore((state) => state.clearSession);

  const handleApiError = ({ error, onValidationError, notify }) => {
    const code = getApiErrorCode(error);
    const message = getApiErrorMessage(error);

    if (code === 'unauthenticated') {
      clearSession();
    }

    if (code === 'validation_error' && onValidationError) {
      onValidationError(getApiValidationDetails(error));
    }

    if (notify) {
      notify(message);
    }

    return { code, message };
  };

  return { handleApiError };
};
