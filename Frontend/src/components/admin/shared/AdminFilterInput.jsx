import { cn } from '../../../lib/utils';

export const AdminFilterInput = ({ className, ...props }) => {
  return (
      <input
          className={cn(
              "w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500",
              className
          )}
          {...props}
      />
  );
};