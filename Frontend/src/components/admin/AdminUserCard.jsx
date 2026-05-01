import React from 'react';
import { Ban, Key, Unlock } from 'lucide-react';
import { clsx } from 'clsx';
import { AnimatedCard } from '../animations/AnimatedCard';

export const AdminUserCard = ({ user, onToggleBan, onRevoke, isBanning, isUnbanning, isRevoking }) => {
  return (
    <AnimatedCard className="bg-white border text-sm border-slate-100 shadow-sm hover:shadow-md transition-shadow rounded-3xl p-5 flex flex-col gap-3 relative overflow-hidden ring-1 ring-slate-900/5 group">
      <div className="flex justify-between items-start">
        <div className="flex flex-col">
          <span className="font-bold text-slate-800 text-base">{user.name}</span>
          <span className="text-slate-500 text-xs">{user.email}</span>
          <div className="mt-2 flex gap-2">
            <span className={clsx("px-2.5 py-1 rounded-lg text-xs font-bold w-fit",
              user.role === 'admin' ? "bg-purple-100 text-purple-700" :
                user.role === 'verified' ? "bg-blue-100 text-blue-700" : "bg-slate-100 text-slate-600"
            )}>
              {user.role.toUpperCase()}
            </span>
            {user.is_banned && (
              <span className="px-2.5 py-1 rounded-lg text-xs font-bold w-fit bg-red-100 text-red-700">
                BANEADO
              </span>
            )}
          </div>
        </div>
      </div>

      <div className="flex justify-end gap-2 mt-auto pt-3 border-t border-slate-100">
        <button
          onClick={() => onToggleBan(user)}
          disabled={isUnbanning || isBanning}
          className={clsx(
            "flex items-center justify-center p-2 rounded-xl transition-all shadow-sm",
            user.is_banned
              ? "bg-green-50 text-green-600 hover:bg-green-600 hover:text-white"
              : "bg-red-50 text-red-600 hover:bg-red-600 hover:text-white",
            (isUnbanning || isBanning) && "opacity-50 cursor-not-allowed"
          )}
          title={user.is_banned ? "Desbanear" : "Banear"}
        >
          {user.is_banned ? <Unlock size={18} /> : <Ban size={18} />}
        </button>
        <button
          onClick={() => onRevoke(user)}
          disabled={isRevoking}
          className={clsx(
            "flex items-center justify-center p-2 rounded-xl transition-all shadow-sm bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white",
            isRevoking && "opacity-50 cursor-not-allowed"
          )}
          title="Revocar sesiones"
        >
          <Key size={18} />
        </button>
      </div>
    </AnimatedCard>
  );
};