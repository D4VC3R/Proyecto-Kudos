import React from 'react';
import { Link } from 'react-router-dom';
import Button from '../ui/Button.jsx';
import Chip from '../ui/Chip.jsx';
import DataTable from '../ui/DataTable.jsx';
import AdminStatusChip from './AdminStatusChip.jsx';

const getRoleLabel = (user) => (user.role === 'admin' ? 'ADMIN' : (user.is_verified ? 'USUARIO' : 'INVITADO'));

const AdminUsersTable = ({ users, onToggleBan, onRevoke, isBanning, isUnbanning, isRevoking }) => {
  const columns = [
    {
      id: 'user',
      header: 'Usuario',
      cell: (user) => (
        <>
          <Link
            to={`/admin/users/${user.id}`}
            className="rounded-sm font-semibold text-ink underline-offset-3 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
          >
            {user.name}
          </Link>
          <p className="text-caption text-ink-muted">{user.email}</p>
        </>
      ),
    },
    {
      id: 'role',
      header: 'Rol',
      cell: (user) => <Chip>{getRoleLabel(user)}</Chip>,
    },
    {
      id: 'status',
      header: 'Estado',
      cell: (user) => (
        <AdminStatusChip tone={user.is_banned ? 'danger' : 'success'}>
          {user.is_banned ? 'BANEADO' : 'ACTIVO'}
        </AdminStatusChip>
      ),
    },
    {
      id: 'actions',
      header: 'Acciones',
      align: 'end',
      cell: (user) => (
        <div className="flex justify-end gap-2">
          {user.is_banned ? (
            <Button variant="secondary" onClick={() => onToggleBan(user)} disabled={isBanning || isUnbanning}>
              Desbanear
            </Button>
          ) : (
            <Button variant="ghost" className="text-danger" onClick={() => onToggleBan(user)} disabled={isBanning || isUnbanning}>
              Banear
            </Button>
          )}
          <Button variant="secondary" onClick={() => onRevoke(user)} disabled={isRevoking}>
            Revocar sesiones
          </Button>
        </div>
      ),
    },
  ];

  return <DataTable caption="Usuarios" columns={columns} rows={users}/>;
};

export default AdminUsersTable;
