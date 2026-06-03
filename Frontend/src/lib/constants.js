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
  BarChart3, Trophy, Medal
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

// Configuración del ranking, con estilos específicos para los primeros 3 puestos y un estilo genérico para el resto.
export const RANK_CONFIG = {
  1: {
    wrapperClass: 'bg-gradient-to-r from-yellow-200 to-white border-accent shadow-[20px_0_25px_rgba(250,204,21,0.5)] z-30 border-2 py-5 px-5',
    iconWrapperClass: 'h-12 w-12',
    Icon: Trophy,
    iconSize: 28,
    iconClass: 'text-yellow-500 drop-shadow-sm',
    textClass: 'text-xl',
    scoreClass: 'px-3 py-1.5 text-base'
  },
  2: {
    wrapperClass: 'bg-gradient-to-r from-slate-300 to-white border-slate-500 shadow-[20px_0_20px_rgba(148,163,184,0.5)] z-20 border-2 py-4 px-4',
    iconWrapperClass: 'h-10 w-10',
    Icon: Medal,
    iconSize: 24,
    iconClass: 'text-slate-400 drop-shadow-sm',
    textClass: 'text-lg',
    scoreClass: 'px-2.5 py-1 text-xs'
  },
  3: {
    wrapperClass: 'bg-gradient-to-r from-orange-200 to-white border-orange-400 shadow-[20px_0_20px_rgba(217,119,6,0.5)] z-10 border-2 py-3 px-3',
    iconWrapperClass: 'h-10 w-10',
    Icon: Medal,
    iconSize: 24,
    iconClass: 'text-orange-400 drop-shadow-sm',
    textClass: 'text-lg',
    scoreClass: 'px-2.5 py-1 text-xs'
  },
  default: {
    wrapperClass: 'bg-surface border-slate-300 hover:border-blue-400 border py-1.5 px-3 shadow-md transition-colors',
    iconWrapperClass: 'h-8 w-8',
    Icon: null, // No hay icono por defecto, usaremos el número
    textClass: 'text-sm',
    scoreClass: 'px-2.5 py-1 text-xs'
  }
};

// Configuración de colores para las etiquetas del bloque de administración según su estado.
export const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  accepted: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  changes_requested: 'bg-orange-100 text-orange-700',
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-slate-100 text-slate-700'
};

// Iconos asociados a cada categoría.
export const categoryIcons = {
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

// Objeto con la configuración de las pestañas para el panel de administración.
export const adminTabs = [
  { name: 'Usuarios', to: '/admin/users', icon: Users },
  { name: 'Propuestas', to: '/admin/proposals', icon: FileText },
  { name: 'Categorías', to: '/admin/categories', icon: LayoutGrid },
  { name: 'Items', to: '/admin/items', icon: Target },
];

// Objeto con la configuración de las pestañas para el panel de perfil.
export const profileTabs = [
  { id: 'info', name: 'Información', icon: UserIcon },
  { id: 'proposals', name: 'Mis Propuestas', icon: List },
  { id: 'votes', name: 'Historial de Votos', icon: ThumbsUp },
  { id: 'stats', name: 'Estadísticas', icon: BarChart3 },
];

// Clases de Tailwind para el tamaño de las estrellas en la sección de votación..
export const STAR_SIZE_CLASSES = "w-6 h-6 min-[400px]:w-7 min-[400px]:h-7 sm:w-8 sm:h-8 md:w-10 md:h-10";
