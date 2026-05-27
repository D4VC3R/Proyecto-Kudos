import React from 'react';

export const SelectFilter = ({value, onChange, options, defaultOption = "Todos", icon: Icon}) => {
  return (
    <div className="relative">
      {Icon && <Icon className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />}
      <select
        className={`rounded-2xl border-border bg-surface py-2.5 ${Icon ? 'pl-10' : 'px-4'} pr-8 text-sm focus:ring-2 focus:ring-blue-500 font-bold text-slate-700`}
        value={value}
        onChange={onChange}
      >
        <option value="">{defaultOption}</option>
        {options.map(opt => (
          <option key={opt.value} value={opt.value}>
            {opt.label}
          </option>
        ))}
      </select>
    </div>
  );
};