import { useState } from 'react';
import { useModal } from '../common/useModal';
import {
  useCreateCategory, useUpdateCategory, useDeleteCategory,
  useAdminModerateItem, useAdminDeleteItem,
  useReviewProposal,
  useBanUser, useUnbanUser, useRevokeUserSessions
} from './useAdminMutations';


export const useAdminCategoryActions = () => {
  const { isOpen, modalType, modalData: selectedCat, openModal, closeModal } = useModal();
  const [formData, setFormData] = useState({ name: '', description: '' });

  const { mutate: createCat, isPending: isCreating } = useCreateCategory();
  const { mutate: updateCat, isPending: isUpdating } = useUpdateCategory();
  const { mutate: deleteCat, isPending: isDeleting } = useDeleteCategory();

  const handleOpenAction = (cat, type) => {
    openModal(type, cat);
    if (type === 'edit') setFormData({ name: cat.name, description: cat.description });
    if (type === 'create') setFormData({ name: '', description: '' });
  };

  const handleCloseModal = () => {
    closeModal();
    setFormData({ name: '', description: '' });
  };

  const executeAction = () => {
    if (modalType === 'create') {
      createCat(formData, { onSuccess: handleCloseModal });
    } else if (modalType === 'edit') {
      updateCat({ slug: selectedCat.slug, data: formData }, { onSuccess: handleCloseModal });
    } else if (modalType === 'delete') {
      deleteCat(selectedCat.slug, { onSuccess: handleCloseModal });
    }
  };

  return {
    isOpen, modalType, selectedCat, handleCloseModal,
    handleOpenAction, executeAction,
    isPending: isCreating || isUpdating || isDeleting,
    formData, setFormData,
  };
};


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
    isOpen, actionType, selectedItem, closeModal,
    handleOpenAction, executeAction,
    isPending: isModerating || isDeleting,
    modStatus, setModStatus, modReason, setModReason,
  };
};

// --- PROPOSAL ACTIONS ---
export const useAdminProposalActions = () => {
  const { isOpen, modalType: actionType, modalData: selectedProposal, openModal, closeModal } = useModal();
  const [adminNotes, setAdminNotes] = useState('');
  const { mutate: reviewProposal, isPending } = useReviewProposal();

  const handleOpenAction = (proposal, type) => {
    openModal(type, proposal);
    setAdminNotes('');
  };

  const executeAction = () => {
    if (!selectedProposal) return;
    reviewProposal({
      id: selectedProposal.id,
      status: actionType,
      admin_notes: adminNotes
    }, { onSuccess: closeModal });
  };

  return {
    isOpen, actionType, selectedProposal, closeModal,
    handleOpenAction, executeAction, isPending,
    adminNotes, setAdminNotes,
  };
};

// --- USER ACTIONS ---
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
    isOpen, modalType, selectedUser, closeModal,
    handleOpenAction, handleToggleBan, executeAction,
    isPending: isBanning || isRevoking,
    isBanning, isUnbanning, isRevoking,
    banParams, setBanParams,
  };
};
