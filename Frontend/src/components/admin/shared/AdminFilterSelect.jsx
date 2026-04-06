import { cn } from '../../../lib/utils';

export const AdminFilterSelect = ({ className, options = [], ...props }) => {
  return (
      <select
          className={cn(
              "rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500",
              className
          )}
          {...props}
      >
        {options.map((option) => (
            <option key={String(option.value)} value={option.value}>
              {option.label}
            </option>
        ))}
      </select>
  );
};