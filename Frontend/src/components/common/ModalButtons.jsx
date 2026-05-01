import React from 'react';
import {clsx} from 'clsx';

export const ModalButtons = ({onClose, onConfirm, isPending, confirmText = 'Confirmar', cancelText = 'Cancelar', actionStyle = 'primary' }) => {
  const getActionColors = () => {
    switch (actionStyle) {
      case 'danger':
        return 'bg-red-500 hover:bg-red-600';
      case 'warning':
        return 'bg-orange-500 hover:bg-orange-600';
      case 'success':
        return 'bg-green-500 hover:bg-green-600';
      case 'primary':
      default:
        return 'bg-blue-600 hover:bg-blue-700';
    }
  };

  return (
    <>
      <button
        onClick={onClose}
        disabled={isPending}
        className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-bold transition-colors disabled:opacity-50"
      >
        {cancelText}
      </button>
      <button
        onClick={onConfirm}
        disabled={isPending}
        className={clsx(
          "px-4 py-2 rounded-xl font-bold text-white transition-colors shadow-sm disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed",
          getActionColors()
        )}
      >
        {isPending ? 'Procesando...' : confirmText}
      </button>
    </>
  );
};