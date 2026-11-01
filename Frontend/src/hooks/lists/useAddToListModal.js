import { useState } from 'react';
import { useAddToListStore } from '../../store/useAddToListStore.js';
import { useMyLists } from './useListQueries';
import { useAddMyListItem, useCreateMyList, useRemoveMyListItem } from './useListMutations';

/**
 * Lógica del modal "Añadir a una lista" (F3-01): lista las listas propias marcando cuáles contienen el ítem,
 * añade o quita con un clic y permite crear una lista nueva y añadir el ítem a la vez.
 *
 * @returns {object} `state` y `actions`.
 */
export const useAddToListModal = () => {
  const item = useAddToListStore((state) => state.item);
  const closeAddToList = useAddToListStore((state) => state.closeAddToList);

  const { data: response, isLoading, isError } = useMyLists({ itemId: item?.id, enabled: Boolean(item) });
  const lists = response?.data ?? [];
  const meta = response?.meta ?? {};

  const [title, setTitle] = useState('');
  const [visibility, setVisibility] = useState('private');

  const { mutate: addItem, isPending: isAdding } = useAddMyListItem();
  const { mutate: removeItem, isPending: isRemoving } = useRemoveMyListItem();
  const { mutate: createList, isPending: isCreating } = useCreateMyList();

  const isBusy = isAdding || isRemoving || isCreating;
  const canCreate = (meta.total ?? lists.length) < (meta.max_lists ?? 50);

  const resetForm = () => {
    setTitle('');
    setVisibility('private');
  };

  const handleClose = () => {
    resetForm();
    closeAddToList();
  };

  const handleToggle = (list) => {
    if (!item) return;
    if (list.contains_item) removeItem({ listId: list.id, itemId: item.id });
    else addItem({ listId: list.id, itemId: item.id });
  };

  const handleCreateAndAdd = () => {
    if (!item || !title.trim()) return;
    createList(
      { title: title.trim(), visibility },
      {
        onSuccess: (created) => {
          resetForm();
          addItem({ listId: created.data.id, itemId: item.id });
        },
      },
    );
  };

  return {
    state: { item, lists, meta, title, visibility, isLoading, isError, isBusy, isCreating, canCreate },
    actions: { setTitle, setVisibility, handleToggle, handleCreateAndAdd, handleClose },
  };
};
