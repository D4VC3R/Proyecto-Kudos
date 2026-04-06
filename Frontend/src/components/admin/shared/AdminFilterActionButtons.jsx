export const AdminFilterActionButtons = ({
  primaryLabel = 'Filtrar',
  onSecondary,
  secondaryLabel = 'Limpiar',
  primaryType = 'submit',
  className = '',
}) => {
  return (
    <div className={`flex gap-2 ${className}`.trim()}>
      {primaryLabel ? (
        <button className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500" type={primaryType}>
          {primaryLabel}
        </button>
      ) : null}
      <button
        className="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800"
        onClick={onSecondary}
        type="button"
      >
        {secondaryLabel}
      </button>
    </div>
  );
};

