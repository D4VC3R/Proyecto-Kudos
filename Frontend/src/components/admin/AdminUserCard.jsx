import React from 'react';
import { Ban, Key, Unlock } from 'lucide-react';
import { clsx } from 'clsx';
import { useNavigate } from 'react-router-dom';
import  AnimatedCard  from '../animations/AnimatedCard';
import  Button  from '../ui/Button.jsx';

const AdminUserCard = ({ user, onToggleBan, onRevoke, isBanning, isUnbanning, isRevoking }) => {
  const navigate = useNavigate();

  return (
    <AnimatedCard
      onClick={() => navigate(`/admin/users/${user.id}`)}
      className="bg-surface border cursor-pointer text-sm border-slate-100 shadow-sm hover:shadow-md transition-shadow rounded-3xl p-5 flex flex-col gap-3 relative overflow-hidden ring-1 ring-slate-900/5 group"
    >
      <div className="flex justify-between items-start">
        <div className="flex flex-col">
          <span className="font-bold text-slate-800 text-base">{user.name}</span>
          <span className="text-text-normal text-xs">{user.email}</span>
          <div className="mt-2 flex gap-2">
            <span className={clsx("px-2.5 py-1 rounded-lg text-xs font-bold w-fit",
              user.role === 'admin' ? "bg-purple-100 text-purple-700" :
                user.is_verified ? "bg-blue-100 text-blue-700" : "bg-slate-100 text-nav-item"
            )}>
              {user.role === 'admin' ? 'ADMIN' : (user.is_verified ? 'VERIFIED' : 'GUEST')}
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
        <Button
          onClick={(e) => { e.stopPropagation(); onToggleBan(user); }}
          disabled={isUnbanning || isBanning}
          title={user.is_banned ? "Desbanear" : "Banear"}
          variant="ghost"
          color={user.is_banned ? 'success' : 'danger'}
          size="iconMd"
          icon={user.is_banned ? Unlock : Ban}
        />
        <Button
          onClick={(e) => { e.stopPropagation(); onRevoke(user); }}
          disabled={isRevoking}
          title="Revocar sesiones"
          variant="ghost"
          color="warning"
          size="iconMd"
          icon={Key}
        />
      </div>
    </AnimatedCard>
  );
};

export default AdminUserCard;