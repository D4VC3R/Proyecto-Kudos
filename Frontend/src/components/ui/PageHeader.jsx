import React from 'react';
import clsx from 'clsx';

// Cabecera de una página: siempre lleva el único `h1`. `display` es la de la portada y `page` la del resto.
// Las acciones (`children`) van a la derecha desde `md`.
const PageHeader = ({ title, highlight, kicker, subtitle, size = 'page', children }) => (
  <header className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
      {kicker && (
        <p className="mb-2 text-caption font-semibold uppercase tracking-widest text-kicker">{kicker}</p>
      )}
      <h1 className={clsx('font-display text-ink', size === 'display' ? 'text-display' : 'text-h1')}>
        {title} {highlight && <span className="text-accent">{highlight}</span>}
      </h1>
      {subtitle && <p className="mt-3 max-w-2xl text-lg text-ink-muted">{subtitle}</p>}
    </div>
    {children && <div className="flex flex-wrap gap-2 sm:gap-4">{children}</div>}
  </header>
);

export default PageHeader;
