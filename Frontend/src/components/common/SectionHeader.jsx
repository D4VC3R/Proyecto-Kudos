import React from 'react';
export const SectionHeader = ({ title, highlight, highlightColor = 'blue-600', subtitle, children }) => {
  return (
    <div className="flex flex-col md:flex-row md:items-end justify-between border-b border-slate-200 pb-4">
      <div>
        <h2 className="text-2xl font-black tracking-tight text-slate-900">
          {title} {highlight && <span className={`text-${highlightColor} drop-shadow-sm`}>{highlight}</span>}
        </h2>
        {subtitle && (
          <p className="mt-1 text-sm text-slate-500 font-medium">
            {subtitle}
          </p>
        )}
      </div>
      {(children) && (
        <div className="mt-4 md:mt-0 flex gap-2 sm:gap-4 flex-wrap">
          {children}
        </div>
      )}
    </div>
  );
};
