import React from 'react';
import { Key } from 'lucide-react';
import { clsx } from 'clsx';

export const RevokeSessionsButton = ({ onClick, isDisabled, fullWidth = false }) => {
  return (
    <button
      onClick={onClick}
      disabled={isDisabled}
      title={!fullWidth ? "Revocar sesiones" : undefined}
      className={clsx(
        "flex items-center justify-center transition-all shadow-sm",
        fullWidth
          ? "gap-2 px-4 py-3 rounded-xl font-bold bg-orange-100 text-orange-700 hover:bg-orange-200 w-full"
          : "p-2 rounded-xl cursor-pointer bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white",
        isDisabled && "opacity-50 cursor-not-allowed pointer-events-none"
      )}
    >
      <Key size={18} />
      {fullWidth && "Revocar Sesiones"}
    </button>
  );
};