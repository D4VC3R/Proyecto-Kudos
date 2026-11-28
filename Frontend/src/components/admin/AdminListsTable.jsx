import React from 'react';
import { Link } from 'react-router-dom';
import Button from '../ui/Button.jsx';
import DataTable from '../ui/DataTable.jsx';
import { hasText } from '../../lib/text.js';

const AdminListsTable = ({ lists, onEdit, onDelete }) => {
  const columns = [
    {
      id: 'list',
      header: 'Lista',
      cell: (list) => (
        <>
          <p className="font-semibold text-ink">{list.title}</p>
          <p className="text-caption text-ink-muted">/listas/{list.slug}</p>
        </>
      ),
    },
    {
      id: 'items',
      header: 'Títulos',
      cell: (list) => list.items_count,
    },
    {
      id: 'description',
      header: 'Descripción',
      cell: (list) => hasText(list.description) && (
        <p className="line-clamp-2 max-w-md">{list.description}</p>
      ),
    },
    {
      id: 'actions',
      header: 'Acciones',
      align: 'end',
      cell: (list) => (
        <div className="flex justify-end gap-2">
          <Button as={Link} to={`/admin/lists/${list.id}`} variant="secondary">Ítems</Button>
          <Button variant="secondary" onClick={() => onEdit(list)}>Editar</Button>
          <Button variant="ghost" className="text-danger" aria-label="Eliminar lista" onClick={() => onDelete(list)}>
            Eliminar
          </Button>
        </div>
      ),
    },
  ];

  return <DataTable caption="Listas editoriales" columns={columns} rows={lists}/>;
};

export default AdminListsTable;
