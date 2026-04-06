import { cn } from '../../../lib/utils';

export const AdminSortHeaderButton = ({ field, label, sortBy, sortDirection, onSort, className }) => {
  const isActive = sortBy === field;
  const ariaSort = isActive ? (sortDirection === 'desc' ? 'descending' : 'ascending') : 'none';

  return (
      <button
          aria-sort={ariaSort}
          className={cn("inline-flex items-center gap-1 font-medium text-slate-300 transition-colors hover:text-slate-100", className)}
          onClick={() => onSort(field)}
          type="button"
      >
        {label}
        <span aria-hidden="true" className="text-xs text-slate-500">
        {isActive ? (sortDirection === 'desc' ? '↓' : '↑') : '-'}
      </span>
      </button>
  );
};