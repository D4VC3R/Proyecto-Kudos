import React from 'react';
import { Link } from 'react-router-dom';
import Button from '../ui/Button.jsx';
import Chip from '../ui/Chip.jsx';
import DataTable from '../ui/DataTable.jsx';
import AdminStatusChip from './AdminStatusChip.jsx';
import { formatDate } from '../../lib/formatters.js';
import { REPORTABLE_TYPE_LABELS } from '../../lib/reports.js';
import { hasText } from '../../lib/text.js';

const AdminReportsTable = ({ reports, onHide, onDelete, onDismiss }) => {
  const columns = [
    {
      id: 'report',
      header: 'Reporte',
      cell: (report) => (
        <div className="flex flex-col items-start gap-2">
          <Chip>{REPORTABLE_TYPE_LABELS[report.reportable_type] ?? report.reportable_type}</Chip>
          <AdminStatusChip status={report.status}/>
        </div>
      ),
    },
    {
      id: 'reason',
      header: 'Motivo',
      cell: (report) => (
        <>
          <p className="font-semibold text-ink">{report.reason_label}</p>
          {hasText(report.details) && (
            <p className="max-w-sm break-words text-caption text-ink-muted">{report.details}</p>
          )}
        </>
      ),
    },
    {
      id: 'content',
      header: 'Contenido',
      cell: ({ content }) => (content ? (
        <>
          <p className="line-clamp-3 max-w-sm break-words">{content.excerpt}</p>
          <div className="mt-2 flex flex-wrap items-center gap-2 text-caption text-ink-muted">
            {content.author && <span>De: <span className="font-semibold text-ink">{content.author.name}</span></span>}
            {content.is_hidden && <AdminStatusChip tone="warning">Oculto</AdminStatusChip>}
            {content.is_deleted && <AdminStatusChip tone="danger">Borrado</AdminStatusChip>}
          </div>
          {content.url && (
            <Link
              to={content.url}
              className="mt-2 inline-block rounded-sm text-small text-accent underline underline-offset-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              Ver contenido
            </Link>
          )}
        </>
      ) : (
        <p className="text-ink-muted">El contenido ya no existe</p>
      )),
    },
    {
      id: 'reported',
      header: 'Reportado',
      cell: (report) => (
        <div className="text-caption text-ink-muted">
          <p>
            Por <span className="font-semibold text-ink">{report.reporter?.name ?? 'Usuario eliminado'}</span>
          </p>
          <p>{formatDate(report.created_at)}</p>
          {report.status !== 'pending' && (
            <>
              {hasText(report.resolution_note) && <p className="max-w-sm break-words">Nota: {report.resolution_note}</p>}
              {report.resolver && (
                <p>Resuelto por <span className="font-semibold text-ink">{report.resolver.name}</span></p>
              )}
            </>
          )}
        </div>
      ),
    },
    {
      id: 'actions',
      header: 'Acciones',
      align: 'end',
      cell: (report) => report.status === 'pending' && (
        <div className="flex flex-wrap justify-end gap-2">
          {report.reportable_type !== 'user' && (
            <Button variant="secondary" onClick={() => onHide(report)}>Ocultar</Button>
          )}
          <Button variant="ghost" className="text-danger" onClick={() => onDelete(report)}>
            {report.reportable_type === 'user' ? 'Vaciar perfil' : 'Eliminar'}
          </Button>
          <Button variant="secondary" onClick={() => onDismiss(report)}>Descartar</Button>
        </div>
      ),
    },
  ];

  return <DataTable caption="Cola de reportes" columns={columns} rows={reports}/>;
};

export default AdminReportsTable;
