import React from 'react';
import { Ban, Unlock } from 'lucide-react';
import { clsx } from 'clsx';

export const ToggleBanButton = ({ isBanned, onClick, isDisabled, fullWidth = false }) => {
  return (
    <button
      onClick={onClick}
      disabled={isDisabled}
      title={!fullWidth ? (isBanned ? "Desbanear" : "Banear") : undefined}
      className={clsx(
        "flex items-center justify-center transition-all shadow-sm",
        fullWidth
          ? "gap-2 px-4 py-3 rounded-xl font-bold w-full"
          : "p-2 rounded-xl cursor-pointer",
        isBanned
          ? (fullWidth ? "bg-green-100 text-green-700 hover:bg-green-200" : "bg-green-50 text-green-600 hover:bg-green-600 hover:text-white")
          : (fullWidth ? "bg-red-100 text-red-700 hover:bg-red-200" : "bg-red-50 text-red-600 hover:bg-red-600 hover:text-white"),
        isDisabled && "opacity-50 cursor-not-allowed pointer-events-none"
      )}
    >
      {isBanned ? <Unlock size={18} /> : <Ban size={18} />}
      {fullWidth && (isBanned ? "Desbanear Usuario" : "Suspender Usuario")}
    </button>
  );
};