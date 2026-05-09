export const VARIANTS = {
  solid: {
    primary: 'bg-blue-600 text-white hover:bg-blue-700',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    warning: 'bg-orange-500 text-white hover:bg-orange-600',
    success: 'bg-green-500 text-white hover:bg-green-600',
    neutral: 'bg-slate-200 text-slate-800 hover:bg-slate-300'
  },
  ghost: {
    primary: 'bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white',
    danger: 'bg-red-100 text-red-700 hover:bg-red-700 hover:text-white',
    warning: 'bg-orange-100 text-orange-700 hover:bg-orange-600 hover:text-white',
    success: 'bg-green-100 text-green-700 hover:bg-green-600 hover:text-white',
    neutral: 'bg-slate-100 text-slate-700 hover:bg-slate-300 hover:text-slate-800'
  },
  outline: {
    neutral: 'border-2 text-slate-700 hover:bg-slate-200 hover:text-slate-900',
  },
  tab: {
    active: 'border-b-2 border-blue-600 text-blue-600 rounded-none',
    inactive: 'border-b-2 border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 rounded-none'
  },
  ring: {
    primary: 'bg-blue-600 text-white shadow-sm shadow-blue-500/30 ring-2 ring-transparent hover:ring-blue-600 hover:ring-offset-2 hover:ring-offset-slate-50',
    danger: 'bg-red-600 text-white shadow-sm shadow-red-500/30 ring-2 ring-transparent hover:ring-red-600 hover:ring-offset-2 hover:ring-offset-slate-50',
    navActive: 'bg-blue-600 text-white shadow-md shadow-blue-500/30 ring-2 ring-blue-600 ring-offset-2 ring-offset-slate-50',
    navInactive: 'text-slate-600 ring-2 ring-transparent hover:bg-slate-100 hover:text-slate-900 hover:shadow-sm'
  }
};

export const SIZES = {
  sm: 'px-4 py-1.5 text-sm',
  md: 'px-4 py-2.5 text-sm',
  lg: 'px-4 py-3 text-base',
  iconSm: 'p-1.5',
  iconMd: 'p-2.5',
  iconLg: 'h-10 w-10',
};

export const RADII = {
  md: 'rounded-md',
  lg: 'rounded-lg',
  xl: 'rounded-xl',
  full: 'rounded-full',
};