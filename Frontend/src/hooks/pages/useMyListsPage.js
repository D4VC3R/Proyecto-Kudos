import { useState } from 'react';
import { useModal } from '../common/useModal';
import { useMyLists } from '../lists/useListQueries';
import { useCreateMyList, useDeleteMyList } from '../lists/useListMutations';

const EMPTY_FORM = { title: '', description: '', visibility: 'private', is_ranked: false };

/**
 * Hook para la página "Mis listas" (/mis-listas, F3-01): listado, creación y borrado con confirmación.
 *
 * @returns {object} `state` (listas, contadores y modal) y `actions`.
 */
export const useMyListsPage = () => {
  const { data: response, isLoading, isError } = useMyLists();
  const lists = response?.data ?? [];
  const total = response?.meta?.total ?? lists.length;
  const maxLists = response?.meta?.max_lists ?? 50;

  const { isOpen, modalType, modalData: selectedList, openModal, closeModal } = useModal();
  const [formData, setFormData] = useState(EMPTY_FORM);

  const { mutate: createList, isPending: isCreating } = useCreateMyList();
  const { mutate: deleteList, isPending: isDeleting } = useDeleteMyList();

  const handleOpenCreate = () => {
    setFormData(EMPTY_FORM);
    openModal('create');
  };

  const handleOpenDelete = (list) => openModal('delete', list);

  const handleCloseModal = () => {
    closeModal();
    setFormData(EMPTY_FORM);
  };

  const executeAction = () => {
    if (modalType === 'create') {
      if (!formData.title.trim()) return;
      createList({ ...formData, title: formData.title.trim() }, { onSuccess: handleCloseModal });
    } else if (modalType === 'delete' && selectedList) {
      deleteList(selectedList.id, { onSuccess: handleCloseModal });
    }
  };

  return {
    state: {
      lists,
      total,
      maxLists,
      canCreate: total < maxLists,
      isLoading,
      isError,
      isOpen,
      modalType,
      selectedList,
      formData,
      isPending: isCreating || isDeleting,
    },
    actions: { setFormData, handleOpenCreate, handleOpenDelete, handleCloseModal, executeAction },
  };
};
