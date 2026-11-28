import React from 'react';
import { UserSquare, Ban, Unlock, Key } from 'lucide-react';
import BanInfoAlert from './BanInfoAlert';
import AdminStatusChip from './AdminStatusChip.jsx';
import Button from '../ui/Button.jsx';
import Card from '../ui/Card.jsx';
import Chip from '../ui/Chip.jsx';

const AdminUserIdCard = ({ user, isBanning, isUnbanning, isRevoking, onToggleBan, onRevokeSessions }) => {
  return (
    <Card className="flex flex-col gap-6 p-6 lg:col-span-1">
      <div className="flex flex-col items-center text-center gap-3">
        <div className="w-24 h-24 bg-line text-ink-muted rounded-full flex items-center justify-center">
          <UserSquare size={48} aria-hidden="true" />
        </div>
        <div>
          <h3 className="font-display text-h3 text-ink">{user.name}</h3>
          <p className="text-small text-ink-muted">{user.email}</p>
        </div>

        <div className="flex flex-wrap gap-2 justify-center mt-2">
          <Chip>
            Rol: {user.role === 'admin' ? 'ADMIN' : (user.is_verified ? 'USUARIO' : 'INVITADO')}
          </Chip>
          <AdminStatusChip tone={user.is_banned ? 'danger' : 'success'}>
            Estado: {user.ban_state_label?.toUpperCase() || (user.is_banned ? "SUSPENDIDO" : "ACTIVO")}
          </AdminStatusChip>
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
          variant={user.is_banned ? 'primary' : 'danger'}
          isFullWidth
          icon={user.is_banned ? Unlock : Ban}
        >
          {user.is_banned ? "Desbanear usuario" : "Suspender usuario"}
        </Button>
        <Button
          onClick={onRevokeSessions}
          disabled={isRevoking}
          variant="secondary"
          isFullWidth
          icon={Key}
        >
          Cerrar sesiones
        </Button>
      </div>
    </Card>
  );
};

export default AdminUserIdCard;
