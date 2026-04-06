import { getApiErrorCode, getApiErrorMessage, getApiValidationDetails } from '../lib/apiErrorMap';

export const useApiErrorHandler = () => {

  const handleApiError = ({ error, onValidationError, notify }) => {
    const code = getApiErrorCode(error);
    const message = getApiErrorMessage(error);


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
