import React from 'react';
import { ListPlus } from 'lucide-react';
import Button from '../ui/Button.jsx';
import { useAddToListStore } from '../../store/useAddToListStore.js';
import { selectIsAuthenticated, selectIsVerified, useSessionStore } from '../../store/useSessionStore.js';

// Botón "Añadir a lista" (F3-01): abre el modal global. Solo se pinta con sesión verificada.
// variant: 'card' (botón redondo sobre la tarjeta) o 'detail' (botón con texto en la ficha).
const AddToListButton = ({ item, variant = 'card' }) => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const isVerified = useSessionStore(selectIsVerified);
  const openAddToList = useAddToListStore((state) => state.openAddToList);

  if (!isAuthenticated || !isVerified || !item) return null;

  const handleClick = (event) => {
    // En la tarjeta, el botón va dentro del enlace: ni navega ni abre la ficha.
    event.preventDefault();
    event.stopPropagation();
    openAddToList(item);
  };

  if (variant === 'detail') {
    return (
      <Button onClick={handleClick} variant="ghost" color="primary" size="sm" radius="full" icon={ListPlus}>
        Añadir a lista
      </Button>
    );
  }

  return (
    <Button
      onClick={handleClick}
      variant="solid"
      color="neutral"
      size="iconSm"
      radius="full"
      icon={ListPlus}
      className="shadow-md"
      aria-label="Añadir a una lista"
      title="Añadir a una lista"
    />
  );
};

export default AddToListButton;
