import React from 'react';
import { Button } from './Button';

export const ModalButtons = ({onClose, onConfirm, isPending, confirmText = 'Confirmar', cancelText = 'Cancelar', actionStyle = 'primary' }) => {
  const getActionStyleProps = () => {
    switch (actionStyle) {
      case 'danger':
        return { color: 'danger' };
      case 'warning':
        return { color: 'warning' };
      case 'success':
        return { color: 'success' };
      case 'primary':
      default:
        return { color: 'primary' };
    }
  };

  return (
    <>
      <Button
        onClick={onClose}
        disabled={isPending}
        variant="ghost"
        color="neutral"
      >
        {cancelText}
      </Button>
      <Button
        onClick={onConfirm}
        isLoading={isPending}
        variant={actionStyle === 'warning' || actionStyle === 'success' ? 'solid' : 'solid'}
        color={getActionStyleProps().color}
      >
        {confirmText}
      </Button>
    </>
  );
};