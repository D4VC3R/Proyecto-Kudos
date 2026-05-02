import React from 'react';

export const SectionHeader = ({title, highlight, highlightColor = 'blue-600', subtitle, size = 'default', children}) => {
  const isLarge = size === 'large';

  const titleClasses = isLarge
    ? "text-4xl sm:text-5xl md:text-6xl font-black tracking-tight text-slate-900"
    : "text-2xl font-black tracking-tight text-slate-900";

  const subtitleClasses = isLarge
    ? "mt-4 text-lg font-medium text-slate-500 max-w-2xl"
    : "mt-1 text-sm text-slate-500 font-medium";

  return (
    <div className={`flex flex-col md:flex-row md:items-end justify-between ${isLarge ? 'pb-2' : 'border-b border-slate-200 pb-4'}`}>
      <div>
        {isLarge ? (
          <h1 className={titleClasses}>
            {title} {highlight && <span className={`text-${highlightColor} drop-shadow-sm`}>{highlight}</span>}
          </h1>
        ) : (
          <h2 className={titleClasses}>
            {title} {highlight && <span className={`text-${highlightColor} drop-shadow-sm`}>{highlight}</span>}
          </h2>
        )}

        {subtitle && (
          <p className={subtitleClasses}>
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