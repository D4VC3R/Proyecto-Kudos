import React from 'react';
import Button from '../ui/Button.jsx';
import DataTable from '../ui/DataTable.jsx';

const AdminCategoriesTable = ({ categories, onEdit, onDelete }) => {
  const columns = [
    {
      id: 'name',
      header: 'Nombre',
      cell: (category) => (
        <>
          <p className="font-semibold text-ink">{category.name}</p>
          <p className="text-caption text-ink-muted">/{category.slug}</p>
        </>
      ),
    },
    {
      id: 'description',
      header: 'Descripción',
      cell: (category) => (
        <p className="line-clamp-2 max-w-md">{category.description || 'Sin descripción.'}</p>
      ),
    },
    {
      id: 'actions',
      header: 'Acciones',
      align: 'end',
      cell: (category) => (
        <div className="flex justify-end gap-2">
          <Button variant="secondary" onClick={() => onEdit(category)}>Editar</Button>
          <Button variant="ghost" className="text-danger" onClick={() => onDelete(category)}>Eliminar</Button>
        </div>
      ),
    },
  ];

  return <DataTable caption="Categorías" columns={columns} rows={categories}/>;
};

export default AdminCategoriesTable;
