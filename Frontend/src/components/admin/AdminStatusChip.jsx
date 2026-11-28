import React from 'react';
import clsx from 'clsx';
import Chip from '../ui/Chip.jsx';
import { formatStatus } from '../../lib/formatters.js';

const TONES = {
  success: 'bg-success',
  warning: 'bg-warning',
  danger: 'bg-danger',
  neutral: 'bg-ink-muted',
};

const STATUS_TONES = {
  active: 'success',
  accepted: 'success',
  resolved: 'success',
  pending: 'warning',
  changes_requested: 'warning',
  rejected: 'danger',
  inactive: 'neutral',
  dismissed: 'neutral',
};

// Estado de una fila: el estado se lee en el texto y el punto lo refuerza.
const AdminStatusChip = ({ status, tone, children }) => (
  <Chip className="gap-1.5 font-semibold text-ink">
    <span
      aria-hidden="true"
      className={clsx('h-2 w-2 shrink-0 rounded-full', TONES[tone ?? STATUS_TONES[status] ?? 'neutral'])}
    />
    {children ?? formatStatus(status)}
  </Chip>
);

export default AdminStatusChip;
