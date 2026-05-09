import React from 'react';

export const TextAreaField = ({label, labelEnd, icon: Icon, rows = 4, placeholder, registration, error, disabled, ...props}) => {

  return (
    <div>
      <div className="mb-2 flex items-center justify-between">
        <label className="text-sm font-bold text-slate-700">{label}</label>
        {labelEnd && labelEnd}
      </div>
      <div className="relative">
        {Icon && (
          <div className="pointer-events-none absolute left-0 top-3 flex pl-3 text-slate-400">
            <Icon size={18}/>
          </div>)}
        <textarea
          rows={rows}
          disabled={disabled}
          placeholder={placeholder}
          {...(registration || {})}
          {...props}
          className={`w-full resize-none rounded-xl border border-slate-200 bg-slate-50 py-3 ${Icon ? 'pl-10' : 'pl-4'} pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50`}
        />
      </div>
      {error && <span className="mt-1 block text-xs text-red-500">{error.message || error}</span>}
    </div>
  );
};