import React, { useId } from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';

// Tabla de datos con región desplazable (con el teclado también). `columns: [{ id, header, cell: (row, index) => node,
// align?: 'end', className? }]`. Cabecera fija y filas con filete. Cada acción lleva texto o `aria-label`: el color
// nunca es lo único que la distingue.
const DataTable = ({ caption, columns, rows, getRowKey = (row) => row.id, className }) => {
  const captionId = useId();

  return (
    <div
      role="region"
      aria-labelledby={captionId}
      tabIndex={0}
      className={twMerge(clsx(
        'max-h-table overflow-auto rounded-md border border-line bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        className,
      ))}
    >
      <table className="w-full border-separate border-spacing-0 text-left text-small text-ink">
        <caption id={captionId} className="sr-only">{caption}</caption>
        <thead className="sticky top-0 z-10 bg-surface">
          <tr>
            {columns.map((col) => (
              <th
                key={col.id}
                scope="col"
                className={clsx(
                  'whitespace-nowrap border-b border-line-strong px-4 py-3 text-caption font-semibold uppercase tracking-wide text-ink-muted',
                  col.align === 'end' && 'text-right',
                )}
              >
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row, index) => (
            <tr key={getRowKey(row)}>
              {columns.map((col) => (
                <td
                  key={col.id}
                  className={clsx(
                    'px-4 py-3 align-top',
                    index > 0 && 'border-t border-line',
                    col.align === 'end' && 'text-right',
                    col.className,
                  )}
                >
                  {col.cell(row, index)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default DataTable;
