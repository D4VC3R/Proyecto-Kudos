import React from 'react';
import { UserSquare, Ban, Unlock, Key } from 'lucide-react';
import { AnimatedCard } from '../animations/AnimatedCard';
import { BanInfoAlert } from './BanInfoAlert';
import clsx from 'clsx';
import { Button } from '../common/Button';

export const AdminUserIdCard = ({ user, isBanning, isUnbanning, isRevoking, onToggleBan, onRevokeSessions }) => {
  return (
    <AnimatedCard className="bg-white border text-sm border-slate-100 shadow-sm rounded-3xl p-6 flex flex-col gap-6 lg:col-span-1">
      <div className="flex flex-col items-center text-center gap-3">
        <div className="w-24 h-24 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
          <UserSquare size={48} />
        </div>
        <div>
          <h2 className="text-xl font-black text-slate-800">{user.name}</h2>
          <p className="text-slate-500 text-sm font-medium">{user.email}</p>
        </div>

        <div className="flex flex-wrap gap-2 justify-center mt-2">
          <span className={clsx("px-3 py-1 rounded-xl text-xs font-bold",
            user.role === 'admin' ? "bg-purple-100 text-purple-700" :
            user.is_verified ? "bg-blue-100 text-blue-700" : "bg-slate-100 text-slate-600"
          )}>
            Rol: {user.role === 'admin' ? 'ADMIN' : (user.is_verified ? 'VERIFIED' : 'GUEST')}
          </span>
          <span className={clsx("px-3 py-1 rounded-xl text-xs font-bold",
             user.is_banned ? "bg-red-100 text-red-700" : "bg-green-100 text-green-700"
          )}>
            Estado: {user.ban_state_label?.toUpperCase() || (user.is_banned ? "SUSPENDIDO" : "ACTIVO")}
          </span>
        </div>
      </div>

      {user.is_banned && (
        <BanInfoAlert
          banReason={user.ban_reason}
          bannedAt={user.banned_at}
          bannedUntil={user.banned_until}
          banState={user.ban_state}
        />
      )}

      <div className="flex flex-col gap-2 mt-auto pt-4">
        <Button
          onClick={onToggleBan}
          disabled={isBanning || isUnbanning}
          variant="solid"
          color={user.is_banned ? 'success' : 'danger'}
          isFullWidth
          icon={user.is_banned ? Unlock : Ban}
        >
          {user.is_banned ? "Desbanear Usuario" : "Suspender Usuario"}
        </Button>
        <Button
          onClick={onRevokeSessions}
          disabled={isRevoking}
          variant="solid"
          color="warning"
          isFullWidth
          icon={Key}
        >
          Revocar Sesiones
        </Button>
      </div>
    </AnimatedCard>
  );
};
