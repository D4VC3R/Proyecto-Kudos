import { useState } from 'react';
import { useModal } from '../common/useModal';
import { useBanUser, useUnbanUser, useRevokeUserSessions } from './useAdminUserMutations';

/**
 * Controlador de dominio para las acciones de administración de usuarios.
 * Gestiona el estado de los modales, los parámetros de baneo y las llamadas de mutación.
 */
export const useAdminUserActions = () => {
  const { isOpen, modalType, modalData: selectedUser, openModal, closeModal } = useModal();

  const [banParams, setBanParams] = useState({ reason: '', days: 0, is_permanent: false });

  const { mutate: banUser, isPending: isBanning } = useBanUser();
  const { mutate: unbanUser, isPending: isUnbanning } = useUnbanUser();
  const { mutate: revokeTokens, isPending: isRevoking } = useRevokeUserSessions();

  const handleOpenAction = (user, type) => {
    openModal(type, user);
    if (type === 'ban') {
      setBanParams({ reason: '', days: 0, is_permanent: false });
    }
  };

  const handleToggleBan = (user) => {
    if (user.is_banned) {
      unbanUser(user.id);
    } else {
      handleOpenAction(user, 'ban');
    }
  };

  const executeAction = () => {
    if (!selectedUser) return;

    if (modalType === 'ban') {
      banUser({
        userId: selectedUser.id,
        reason: banParams.reason,
        days: banParams.is_permanent ? null : banParams.days,
        is_permanent: banParams.is_permanent
      }, { onSuccess: closeModal });
    } else if (modalType === 'revoke') {
      revokeTokens(selectedUser.id, { onSuccess: closeModal });
    }
  };

  return {
    isOpen,
    modalType,
    selectedUser,
    closeModal,

    handleOpenAction,
    handleToggleBan,
    executeAction,

    isPending: isBanning || isRevoking,
    isBanning,
    isUnbanning,
    isRevoking,
    
    banParams,
    setBanParams,
  };
};