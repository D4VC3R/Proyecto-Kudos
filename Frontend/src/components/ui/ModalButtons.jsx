import React from 'react';
import Button from './Button.jsx';

const ModalButtons = ({
                        onClose,
                        onConfirm,
                        isPending,
                        confirmText = 'Confirmar',
                        cancelText = 'Cancelar',
                        actionStyle = 'primary'
                      }) => {
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
        variant={'solid'}
        color={actionStyle}
      >
        {confirmText}
      </Button>
    </>
  );
};

export default ModalButtons;