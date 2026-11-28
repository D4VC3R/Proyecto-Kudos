import React from 'react';
import Button from './Button.jsx';

// El sistema solo tiene una acción principal y otra destructiva: `warning`, `success` e `info` pasan a la principal.
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
        variant="secondary"
      >
        {cancelText}
      </Button>
      <Button
        onClick={onConfirm}
        isLoading={isPending}
        variant={actionStyle === 'danger' ? 'danger' : 'primary'}
      >
        {confirmText}
      </Button>
    </>
  );
};

export default ModalButtons;