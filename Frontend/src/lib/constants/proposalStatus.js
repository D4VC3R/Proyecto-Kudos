import { Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-react';

export const PROPOSAL_STATUS_CONFIG = {
  pending: {
    color: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    icon: Clock,
    label: 'Pendiente'
  },
  accepted: {
    color: 'bg-green-100 text-green-800 border-green-200',
    icon: CheckCircle,
    label: 'Aceptada'
  },
  rejected: {
    color: 'bg-red-100 text-red-800 border-red-200',
    icon: XCircle,
    label: 'Rechazada'
  },
  changes_requested: {
    color: 'bg-orange-100 text-orange-800 border-orange-200',
    icon: AlertCircle,
    label: 'Cambios Solicitados'
  }
};

