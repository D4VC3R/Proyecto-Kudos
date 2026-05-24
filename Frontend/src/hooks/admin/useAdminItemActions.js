import { useState } from 'react';
import { useModal } from '../common/useModal';
import { useAdminModerateItem, useAdminDeleteItem } from './useAdminItemMutations';

export const useAdminItemActions = () => {
  const { isOpen, modalType: actionType, modalData: selectedItem, openModal, closeModal } = useModal();

  const [modStatus, setModStatus] = useState('active');
  const [modReason, setModReason] = useState('');

  const { mutate: moderateItem, isPending: isModerating } = useAdminModerateItem();
  const { mutate: deleteItem, isPending: isDeleting } = useAdminDeleteItem();

  const handleOpenAction = (item, type) => {
    openModal(type, item);
    if (type === 'moderate') {
      setModStatus(item.status);
      setModReason('');
    }
  };

  const executeAction = () => {
    if (!selectedItem) return;

    if (actionType === 'moderate') {
      moderateItem(
        { id: selectedItem.id, status: modStatus, reason: modReason },
        { onSuccess: closeModal }
      );
    } else if (actionType === 'delete') {
      deleteItem(selectedItem.id, { onSuccess: closeModal });
    }
  };

  return {
    isOpen,
    actionType,
    selectedItem,
    closeModal,
    handleOpenAction,
    executeAction,
    isPending: isModerating || isDeleting,
    modStatus,
    setModStatus,
    modReason,
    setModReason,
  };
};