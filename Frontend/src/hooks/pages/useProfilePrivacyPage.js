import { useState } from 'react';
import { useExportAccountData, useDeleteAccount } from '../users/useUserMutations';

/**
 * Hook para la pestaña "Privacidad" del perfil (F3-08): exportación de datos y borrado de la cuenta
 * con doble confirmación (aviso y contraseña).
 *
 * @returns {object} `state` (paso del modal, contraseña y estados de carga) y `actions`.
 */
export const useProfilePrivacyPage = () => {
  // step: null (cerrado) | 'confirm' (aviso) | 'password' (contraseña)
  const [step, setStep] = useState(null);
  const [password, setPasswordValue] = useState('');
  const [passwordError, setPasswordError] = useState(null);

  const { mutate: exportData, isPending: isExporting } = useExportAccountData();
  const { mutate: deleteAccount, isPending: isDeleting } = useDeleteAccount();

  const handleExport = () => exportData();

  const openDelete = () => {
    setPasswordValue('');
    setPasswordError(null);
    setStep('confirm');
  };

  const closeDelete = () => {
    if (!isDeleting) setStep(null);
  };

  const goToPassword = () => setStep('password');

  const setPassword = (value) => {
    setPasswordValue(value);
    setPasswordError(null);
  };

  const confirmDelete = () => {
    if (password.trim() === '') {
      setPasswordError('Escribe tu contraseña.');
      return;
    }
    deleteAccount(password, { onError: (error) => setPasswordError(error.message) });
  };

  return {
    state: { step, password, passwordError, isExporting, isDeleting },
    actions: { handleExport, openDelete, closeDelete, goToPassword, setPassword, confirmDelete },
  };
};
