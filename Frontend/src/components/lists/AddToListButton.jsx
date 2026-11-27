import React from 'react';
import { ListPlus } from 'lucide-react';
import Button from '../ui/Button.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';
import { useAddToListStore } from '../../store/useAddToListStore.js';
import { selectIsAuthenticated, selectIsVerified, useSessionStore } from '../../store/useSessionStore.js';

// Botón "Añadir a lista" (F3-01): abre el modal global. Solo se pinta con sesión verificada.
// variant: 'card' (botón de icono bajo la tarjeta) o 'detail' (botón con texto en la ficha).
const AddToListButton = ({ item, variant = 'card' }) => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const isVerified = useSessionStore(selectIsVerified);
  const openAddToList = useAddToListStore((state) => state.openAddToList);

  if (!isAuthenticated || !isVerified || !item) return null;

  const handleClick = () => openAddToList(item);

  if (variant === 'detail') {
    return (
      <Button onClick={handleClick} variant="ghost" color="primary" size="sm" radius="full" icon={ListPlus}>
        Añadir a lista
      </Button>
    );
  }

  return (
    <button
      type="button"
      onClick={handleClick}
      className={`inline-grid h-8 w-8 shrink-0 place-items-center rounded-sm border border-transparent text-ink-muted hover:border-line-strong ${FOCUS_RING}`}
      aria-label={`Añadir «${item.name}» a una lista`}
      title="Añadir a una lista"
    >
      <ListPlus size={18} aria-hidden="true" />
    </button>
  );
};

export default AddToListButton;
