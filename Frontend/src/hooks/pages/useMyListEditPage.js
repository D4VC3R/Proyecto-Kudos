import { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMyList } from '../lists/useListQueries';
import {
  useRemoveMyListItem,
  useReorderMyListItems,
  useUpdateMyList,
  useUpdateMyListItemNote,
} from '../lists/useListMutations';

const toFormData = (list) => ({
  title: list?.title ?? '',
  description: list?.description ?? '',
  visibility: list?.visibility ?? 'private',
  is_ranked: Boolean(list?.is_ranked),
});

/**
 * Hook para la edición de una lista propia (/mis-listas/:listId, F3-01): datos, notas, quitar y reordenar.
 * `order` es el orden local (ids de ítem) que usa el arrastre de framer-motion; se sincroniza con el servidor.
 *
 * @returns {object} `state` y `actions`.
 */
export const useMyListEditPage = () => {
  const { listId } = useParams();
  const navigate = useNavigate();

  const { data: list, isLoading, isError } = useMyList(listId);
  const entries = list?.items ?? [];
  const serverOrderKey = entries.map((entry) => entry.item.id).join(',');

  const [formData, setFormData] = useState(() => toFormData(list));
  const [order, setOrder] = useState([]);
  const orderRef = useRef([]);

  const { mutate: updateList, isPending: isSaving } = useUpdateMyList();
  const { mutate: removeItem, isPending: isRemoving } = useRemoveMyListItem();
  const { mutate: saveNote, isPending: isSavingNote } = useUpdateMyListItemNote();
  const { mutate: reorderItems, isPending: isReordering } = useReorderMyListItems();

  // El formulario se rellena al cargar la lista (y solo entonces: guardar una nota no debe pisar lo que se esté escribiendo).
  useEffect(() => {
    if (list?.id) setFormData(toFormData(list));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [list?.id]);

  // El orden local sigue al del servidor cada vez que cambia (carga, añadir, quitar, reordenar).
  useEffect(() => {
    const ids = serverOrderKey ? serverOrderKey.split(',') : [];
    orderRef.current = ids;
    setOrder(ids);
  }, [serverOrderKey]);

  const entriesById = new Map(entries.map((entry) => [entry.item.id, entry]));
  const orderedEntries = order.map((id) => entriesById.get(id)).filter(Boolean);

  const handleReorderPreview = (ids) => {
    orderRef.current = ids;
    setOrder(ids);
  };

  // Envía el orden al servidor (todos los ids). Si falla, vuelve al orden anterior.
  const sendOrder = (ids) => {
    if (ids.join(',') === serverOrderKey) return;
    reorderItems(
      { listId, itemIds: ids },
      {
        onError: () => {
          const serverIds = serverOrderKey ? serverOrderKey.split(',') : [];
          orderRef.current = serverIds;
          setOrder(serverIds);
        },
      },
    );
  };

  // Al soltar tras arrastrar.
  const handleDragEnd = () => sendOrder(orderRef.current);

  // direction: -1 sube, +1 baja (botones ↑/↓, para teclado y móvil).
  const handleMove = (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= order.length) return;

    const ids = [...order];
    [ids[index], ids[target]] = [ids[target], ids[index]];
    handleReorderPreview(ids);
    sendOrder(ids);
  };

  const handleSaveData = () => {
    if (!formData.title.trim()) return;
    // Con la lista ocultada por moderación no se envía la visibilidad (el servidor solo admite «privada»).
    const { visibility, ...rest } = formData;
    const data = { ...rest, title: formData.title.trim(), ...(list?.is_hidden_by_moderation ? {} : { visibility }) };
    updateList({ id: listId, data });
  };

  const handleSaveNote = (itemId, note) => saveNote({ listId, itemId, note });
  const handleRemove = (itemId) => removeItem({ listId, itemId });
  const handleGoBack = () => navigate('/mis-listas');

  return {
    state: {
      list,
      entries: orderedEntries,
      formData,
      isLoading,
      isError,
      isHiddenByModeration: Boolean(list?.is_hidden_by_moderation),
      isSaving,
      isBusy: isRemoving || isSavingNote || isReordering,
    },
    actions: {
      setFormData,
      handleSaveData,
      handleSaveNote,
      handleRemove,
      handleMove,
      handleReorderPreview,
      handleDragEnd,
      handleGoBack,
    },
  };
};
