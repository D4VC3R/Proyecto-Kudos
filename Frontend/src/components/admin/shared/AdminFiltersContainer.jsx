import { cn } from '../../../lib/utils';

export const AdminFiltersContainer = ({ as: Component = 'section', className, children, ...props }) => {
  return (
      <Component
          className={cn("grid gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4", className)}
          {...props}
      >
        {children}
      </Component>
  );
};