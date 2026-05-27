export const VARIANTS = {
  solid: {
    primary: 'bg-primary text-text-btn hover:bg-blue-700',
    danger: 'bg-red-600 text-text-btn hover:bg-red-700',
    warning: 'bg-orange-500 text-text-btn hover:bg-orange-600',
    success: 'bg-green-500 text-text-btn hover:bg-green-600',
    neutral: 'bg-slate-200 text-slate-800 hover:bg-slate-300'
  },
  ghost: {
    primary: 'bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-text-btn',
    danger: 'bg-red-100 text-red-700 hover:bg-red-700 hover:text-text-btn',
    warning: 'bg-orange-100 text-orange-700 hover:bg-orange-600 hover:text-text-btn',
    success: 'bg-green-100 text-green-700 hover:bg-green-600 hover:text-text-btn',
    neutral: 'bg-slate-100 text-slate-700 hover:bg-slate-300 hover:text-slate-800'
  },
  outline: {
    neutral: 'border-2 text-slate-700 hover:bg-slate-200 hover:text-nav-hover-text',
  },
  tab: {
    active: 'border-b-2 border-primary text-primary rounded-none',
    inactive: 'border-b-2 border-transparent text-nav-item hover:text-nav-hover-text hover:border-slate-300 rounded-none'
  },
  ring: {
    primary: 'bg-primary text-text-btn shadow-sm shadow-blue-500/30 ring-2 ring-transparent hover:ring-primary hover:ring-offset-2 hover:ring-offset-slate-50',
    danger: 'bg-red-600 text-text-btn shadow-sm shadow-red-500/30 ring-2 ring-transparent hover:ring-red-600 hover:ring-offset-2 hover:ring-offset-slate-50',
    navActive: 'bg-primary text-text-btn shadow-md shadow-blue-500/30 ring-2 ring-primary ring-offset-2 ring-offset-slate-50',
    navInactive: 'text-nav-item ring-2 ring-transparent hover:bg-nav-hover-bg hover:text-nav-hover-text hover:shadow-sm'
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