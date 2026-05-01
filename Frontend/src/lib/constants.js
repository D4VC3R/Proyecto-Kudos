import { Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-react';

export const FIELD_LABELS = {
  'seasons': 'Temporadas',
  'release_year': 'Año de Lanzamiento',
  'genre': 'Género',
  'developer': 'Desarrollador',
  'director': 'Director',
  'platform': 'Plataforma',
  'author': 'Autor',
  'pages': 'Páginas',
  'episodes': 'Episodios',
  'studio': 'Estudio de Animación',
  'party': 'Partido'
};

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

export const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  accepted: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  changes_requested: 'bg-orange-100 text-orange-700',
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-slate-100 text-slate-700'
};

