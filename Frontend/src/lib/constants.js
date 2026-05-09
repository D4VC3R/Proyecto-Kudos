import {
  Clock,
  CheckCircle,
  XCircle,
  AlertCircle,
  Building2,
  Landmark,
  Disc,
  Mic2,
  Clapperboard, Tv, Gamepad2, Book, Plane, Users, FileText, LayoutGrid, Target, User as UserIcon, List, ThumbsUp,
  BarChart3
} from 'lucide-react';

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

const categoryIcons = {
  'ciudades': Building2,
  'politicos': Landmark,
  'albumes musicales': Disc,
  'artistas musicales': Mic2,
  'cantantes': Mic2,
  'paises': Plane,
  'peliculas': Clapperboard,
  'series': Tv,
  'videojuegos': Gamepad2,
  'libros': Book,
};

export const getCategoryIcon = (categoryName) => {
  const normalizedName = categoryName
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");

  return categoryIcons[normalizedName] || LayoutGrid;
};

export const adminTabs = [
  { name: 'Usuarios', to: '/admin/users', icon: Users },
  { name: 'Propuestas', to: '/admin/proposals', icon: FileText },
  { name: 'Categorías', to: '/admin/categories', icon: LayoutGrid },
  { name: 'Items', to: '/admin/items', icon: Target },
];

export const profileTabs = [
  { id: 'info', name: 'Información', icon: UserIcon },
  { id: 'proposals', name: 'Mis Propuestas', icon: List },
  { id: 'votes', name: 'Historial de Votos', icon: ThumbsUp },
  { id: 'stats', name: 'Estadísticas', icon: BarChart3 },
];

