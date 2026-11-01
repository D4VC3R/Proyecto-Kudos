import React from 'react';
import { Link } from 'react-router-dom';
import { EyeOff, Trash2, XCircle } from 'lucide-react';
import { clsx } from 'clsx';
import AnimatedCard from '../animations/AnimatedCard.jsx';
import { statusColors } from '../../lib/constants.js';
import { formatStatus, formatDate } from '../../lib/formatters.js';
import { REPORTABLE_TYPE_LABELS } from '../../lib/reports.js';
import { hasText } from '../../lib/text.js';
import Button from '../ui/Button.jsx';

// Tarjeta de un reporte en la cola del admin (F3-04).
const AdminReportCard = ({ report, onHide, onDelete, onDismiss }) => {
  const { content } = report;
  const isUser = report.reportable_type === 'user';

  return (
    <AnimatedCard className="bg-surface border border-slate-100 shadow-sm hover:shadow-md transition-shadow rounded-3xl p-5 flex flex-col gap-3 group relative overflow-hidden">
      <div className="flex justify-between items-start gap-2">
        <span className="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[10px] font-black uppercase whitespace-nowrap">
          {REPORTABLE_TYPE_LABELS[report.reportable_type] ?? report.reportable_type}
        </span>
        <span className={clsx("px-2 py-0.5 rounded-lg text-[10px] font-black uppercase whitespace-nowrap", statusColors[report.status])}>
          {formatStatus(report.status)}
        </span>
      </div>

      <div className="text-sm">
        <p className="font-black text-slate-800">{report.reason_label}</p>
        {hasText(report.details) && <p className="mt-1 text-text-normal break-words">{report.details}</p>}
      </div>

      <div className="rounded-2xl bg-background p-3 text-sm">
        {content ? (
          <>
            <p className="line-clamp-3 break-words text-slate-700">{content.excerpt}</p>
            <div className="mt-2 flex flex-wrap items-center gap-2 text-xs font-medium text-text-normal">
              {content.author && <span>De: <span className="font-bold text-slate-700">{content.author.name}</span></span>}
              {content.is_hidden && <span className="rounded-md bg-orange-100 px-1.5 py-0.5 font-black uppercase text-orange-700">Oculto</span>}
              {content.is_deleted && <span className="rounded-md bg-red-100 px-1.5 py-0.5 font-black uppercase text-red-700">Borrado</span>}
            </div>
            {content.url && (
              <Link to={content.url} className="mt-2 inline-block text-xs font-bold text-primary hover:underline">
                Ver contenido
              </Link>
            )}
          </>
        ) : (
          <p className="italic text-text-normal">El contenido ya no existe</p>
        )}
      </div>

      <div className="text-xs font-medium text-text-normal">
        Reportado por <span className="font-bold text-slate-700">{report.reporter?.name ?? 'Usuario eliminado'}</span>
        {' · '}{formatDate(report.created_at)}
      </div>

      {report.status !== 'pending' && (
        <div className="text-xs font-medium text-text-normal">
          {hasText(report.resolution_note) && <p className="break-words">Nota: {report.resolution_note}</p>}
          {report.resolver && <p>Resuelto por <span className="font-bold text-slate-700">{report.resolver.name}</span></p>}
        </div>
      )}

      {report.status === 'pending' && (
        <div className="flex flex-wrap gap-2 mt-auto pt-4 border-t border-slate-100">
          {!isUser && (
            <Button onClick={() => onHide(report)} variant="ghost" color="warning" icon={EyeOff}>
              Ocultar
            </Button>
          )}
          <Button onClick={() => onDelete(report)} variant="ghost" color="danger" icon={Trash2}>
            {isUser ? 'Vaciar perfil' : 'Eliminar'}
          </Button>
          <Button onClick={() => onDismiss(report)} variant="ghost" color="neutral" icon={XCircle}>
            Descartar
          </Button>
        </div>
      )}
    </AnimatedCard>
  );
};

export default AdminReportCard;
