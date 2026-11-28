import React from 'react';
import { ArrowDown, ArrowUp } from 'lucide-react';
import Button from '../ui/Button.jsx';
import DataTable from '../ui/DataTable.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import AdminStatusChip from './AdminStatusChip.jsx';

const AdminListEntriesTable = ({ entries, isBusy, onMove, onRemove }) => {
  const columns = [
    {
      id: 'position',
      header: 'Puesto',
      cell: (entry) => <span className="font-display text-h3 text-ink">{entry.position}</span>,
    },
    {
      id: 'item',
      header: 'Ítem',
      cell: (entry) => {
        const thumb = entry.item.images?.[0]?.variants?.thumb;
        return (
          <div className="flex items-center gap-3">
            {thumb && (
              <StorageImage src={thumb} alt="" className="h-12 w-9 shrink-0 rounded-sm object-cover"/>
            )}
            <span className="font-semibold text-ink">{entry.item.name}</span>
            {entry.item.status !== 'active' && <AdminStatusChip tone="danger">Inactivo</AdminStatusChip>}
          </div>
        );
      },
    },
    {
      id: 'actions',
      header: 'Acciones',
      align: 'end',
      cell: (entry, index) => (
        <div className="flex justify-end gap-2">
          <Button
            variant="ghost"
            icon={ArrowUp}
            aria-label="Subir"
            onClick={() => onMove(index, -1)}
            disabled={index === 0 || isBusy}
          />
          <Button
            variant="ghost"
            icon={ArrowDown}
            aria-label="Bajar"
            onClick={() => onMove(index, 1)}
            disabled={index === entries.length - 1 || isBusy}
          />
          <Button variant="ghost" className="text-danger" onClick={() => onRemove(entry.item.id)} disabled={isBusy}>
            Quitar
          </Button>
        </div>
      ),
    },
  ];

  return <DataTable caption="Ítems de la lista" columns={columns} rows={entries}/>;
};

export default AdminListEntriesTable;
