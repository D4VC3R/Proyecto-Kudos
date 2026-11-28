import React from 'react';
import Button from '../ui/Button.jsx';
import DataTable from '../ui/DataTable.jsx';
import AdminStatusChip from './AdminStatusChip.jsx';

const AdminProposalsTable = ({ proposals, onAccept, onReject }) => {
  const columns = [
    {
      id: 'proposal',
      header: 'Propuesta',
      cell: (proposal) => (
        <>
          <p className="font-semibold text-ink">{proposal.name}</p>
          <p className="line-clamp-1 max-w-md text-caption text-ink-muted">{proposal.description}</p>
        </>
      ),
    },
    {
      id: 'author',
      header: 'Autor',
      cell: (proposal) => proposal.creator?.name,
    },
    {
      id: 'category',
      header: 'Categoría',
      cell: (proposal) => proposal.category?.name,
    },
    {
      id: 'status',
      header: 'Estado',
      cell: (proposal) => <AdminStatusChip status={proposal.status}/>,
    },
    {
      id: 'actions',
      header: 'Acciones',
      align: 'end',
      cell: (proposal) => proposal.status === 'pending' && (
        <div className="flex justify-end gap-2">
          <Button variant="secondary" onClick={() => onAccept(proposal)}>Aceptar</Button>
          <Button variant="ghost" className="text-danger" onClick={() => onReject(proposal)}>Rechazar</Button>
        </div>
      ),
    },
  ];

  return <DataTable caption="Propuestas" columns={columns} rows={proposals}/>;
};

export default AdminProposalsTable;
