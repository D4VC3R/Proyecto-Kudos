import React from 'react';
import Button from '../ui/Button.jsx';
import DataTable from '../ui/DataTable.jsx';
import ScoreStamp from '../ui/ScoreStamp.jsx';
import AdminStatusChip from './AdminStatusChip.jsx';
import { formatScore, formatVotes } from '../../lib/formatters.js';
import { hasText } from '../../lib/text.js';

const AdminItemsTable = ({ items, onModerate, onDelete }) => {
  const columns = [
    {
      id: 'item',
      header: 'Ítem',
      cell: (item) => (
        <>
          <p className="font-semibold text-ink">{item.name}</p>
          {hasText(item.description) && (
            <p className="line-clamp-1 max-w-md text-caption text-ink-muted">{item.description.trim()}</p>
          )}
        </>
      ),
    },
    {
      id: 'category',
      header: 'Categoría',
      cell: (item) => item.category?.name,
    },
    {
      id: 'status',
      header: 'Estado',
      cell: (item) => <AdminStatusChip status={item.status}/>,
    },
    {
      id: 'score',
      header: 'Puntuación',
      cell: (item) => {
        const score = formatScore(item.vote_avg, item.vote_count);
        return (
          <div className="flex items-center gap-2">
            <ScoreStamp score={score}/>
            {score && <span className="text-caption text-ink-muted">({formatVotes(item.vote_count)})</span>}
          </div>
        );
      },
    },
    {
      id: 'actions',
      header: 'Acciones',
      align: 'end',
      cell: (item) => (
        <div className="flex justify-end gap-2">
          <Button variant="secondary" onClick={() => onModerate(item)}>Moderar</Button>
          <Button variant="ghost" className="text-danger" onClick={() => onDelete(item)}>Eliminar</Button>
        </div>
      ),
    },
  ];

  return <DataTable caption="Ítems" columns={columns} rows={items}/>;
};

export default AdminItemsTable;
