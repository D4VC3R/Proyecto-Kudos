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
  BarChart3, MapPin, BookmarkPlus, EyeOff, ListOrdered, Flag, ShieldCheck
} from 'lucide-react';


 // Configuración para los estados de las propuestas, incluyendo colores, iconos y etiquetas.
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

// Configuración de colores para las etiquetas del bloque de administración según su estado.
export const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  accepted: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  changes_requested: 'bg-orange-100 text-orange-700',
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-slate-100 text-slate-700',
  resolved: 'bg-green-100 text-green-700',
  dismissed: 'bg-slate-100 text-slate-700'
};

// Iconos por slug de categoría para el placeholder de los ítems sin imagen.
export const CATEGORY_SLUG_ICONS = {
  'videojuegos': Gamepad2,
  'albumes-musicales': Disc,
  'artistas-musicales': Mic2,
  'libros': Book,
  'peliculas': Clapperboard,
  'series': Tv,
  'ciudades': Building2,
  'politicos': Landmark,
  'paises': Plane,
  'destinos': MapPin,
};

// Iconos lucide admitidos en `category.icon` (llega en la F1, en kebab-case).
export const CATEGORY_ICONS_BY_NAME = {
  'gamepad-2': Gamepad2,
  'disc': Disc,
  'mic-2': Mic2,
  'book': Book,
  'clapperboard': Clapperboard,
  'tv': Tv,
  'building-2': Building2,
  'landmark': Landmark,
  'plane': Plane,
  'map-pin': MapPin,
  'layout-grid': LayoutGrid,
};

// Icono por defecto cuando la categoría no tiene uno asociado.
export const DEFAULT_CATEGORY_ICON = LayoutGrid;

// Objeto con la configuración de las pestañas para el panel de administración.
export const adminTabs = [
  { name: 'Usuarios', to: '/admin/users', icon: Users },
  { name: 'Propuestas', to: '/admin/proposals', icon: FileText },
  { name: 'Categorías', to: '/admin/categories', icon: LayoutGrid },
  { name: 'Items', to: '/admin/items', icon: Target },
  { name: 'Listas', to: '/admin/lists', icon: ListOrdered },
  { name: 'Reportes', to: '/admin/reports', icon: Flag },
];

// Objeto con la configuración de las pestañas para el panel de perfil.
export const profileTabs = [
  { id: 'info', name: 'Información', icon: UserIcon },
  { id: 'proposals', name: 'Mis Propuestas', icon: List },
  { id: 'votes', name: 'Historial de Votos', icon: ThumbsUp },
  { id: 'stats', name: 'Estadísticas', icon: BarChart3 },
  { id: 'privacy', name: 'Privacidad', icon: ShieldCheck },
];

// Pasos de la votación en dos pasos (F2-04): "¿Lo conoces?", valorar o interés.
export const VOTE_STEPS = {
  KNOW: 'know',
  RATE: 'rate',
  INTEREST: 'interest',
};

// Niveles de valoración (F2-04): texto, nota que se envía y tecla. De peor a mejor, para que 1–5 sigan la fila de números.
export const VOTE_LEVELS = [
  { key: '1', score: 2, label: 'No me gustó' },
  { key: '2', score: 4, label: 'Flojo' },
  { key: '3', score: 6, label: 'Bien' },
  { key: '4', score: 8, label: 'Muy bien' },
  { key: '5', score: 10, label: 'Me encantó' },
];

// Etiqueta, colores e icono de cada tipo de voto del usuario (F2-04). `skip` es el nombre heredado de `not_interested`.
export const VOTE_TYPE_BADGES = {
  vote: { label: 'Votado', color: 'bg-yellow-50 text-yellow-700 border-yellow-200', icon: CheckCircle },
  want: { label: 'En tu wishlist', color: 'bg-blue-50 text-blue-700 border-blue-200', icon: BookmarkPlus },
  not_interested: { label: 'No te interesa', color: 'bg-background text-nav-item border-border', icon: EyeOff },
  skip: { label: 'No te interesa', color: 'bg-background text-nav-item border-border', icon: EyeOff },
};
