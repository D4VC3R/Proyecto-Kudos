import { useState } from 'react';
import { useModal } from '../common/useModal';
import { useCreateCategory, useUpdateCategory, useDeleteCategory } from './useAdminCategoryMutations';

/**
 * Controlador de dominio para la gestión de categorías.
 * Aísla el estado de los modales, el reseteo de los formularios y la ejecución de mutaciones.
 */
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
    isOpen,
    modalType,
    selectedCat,
    handleCloseModal,

    handleOpenAction,
    executeAction,
    isPending: isCreating || isUpdating || isDeleting,

    formData,
    setFormData,
  };
};