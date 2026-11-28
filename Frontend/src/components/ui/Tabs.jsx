import React, { useRef } from 'react';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';

const CONTAINER = 'flex gap-4 overflow-x-auto border-b border-line md:gap-8';
const TAB = 'inline-flex items-center gap-2 whitespace-nowrap rounded-t-sm border-b-2 px-1 py-3 text-small font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
const TAB_ACTIVE = 'border-accent text-ink';
const TAB_INACTIVE = 'border-transparent text-ink-muted hover:border-line-strong hover:text-ink';

// Pestañas. Si todos los elementos llevan `to` son enlaces de navegación (`aria-current="page"`); si no, son
// pestañas de un `tablist` que cambian con `onChange` y se mueven con las flechas, Inicio y Fin.
// `items: [{ id, label, icon?, to?, state? }]`.
// Con `idPrefix`, cada pestaña apunta a su panel (`role="tabpanel"`, lo pinta la página).
const Tabs = ({ items, activeId, onChange, label, className, idPrefix }) => {
  const tabRefs = useRef([]);
  const isNavigation = items.length > 0 && items.every((item) => item.to);
  const containerClass = twMerge(clsx(CONTAINER, className));

  if (isNavigation) {
    return (
      <nav aria-label={label} className={containerClass}>
        {items.map(({ id, label: itemLabel, icon: Icon, to, state }) => {
          const isActive = id === activeId;
          return (
            <Link
              key={id}
              to={to}
              state={state}
              aria-current={isActive ? 'page' : undefined}
              className={clsx(TAB, isActive ? TAB_ACTIVE : TAB_INACTIVE)}
            >
              {Icon && <Icon size={18} aria-hidden="true" className="shrink-0" />}
              {itemLabel}
            </Link>
          );
        })}
      </nav>
    );
  }

  const handleKeyDown = (event, index) => {
    const last = items.length - 1;
    const nextIndex = {
      ArrowRight: index === last ? 0 : index + 1,
      ArrowLeft: index === 0 ? last : index - 1,
      Home: 0,
      End: last,
    }[event.key];

    if (nextIndex === undefined) return;
    event.preventDefault();
    onChange?.(items[nextIndex].id);
    tabRefs.current[nextIndex]?.focus();
  };

  return (
    <div role="tablist" aria-label={label} className={containerClass}>
      {items.map(({ id, label: itemLabel, icon: Icon }, index) => {
        const isActive = id === activeId;
        return (
          <button
            key={id}
            id={idPrefix ? `${idPrefix}-tab-${id}` : undefined}
            aria-controls={idPrefix ? `${idPrefix}-panel-${id}` : undefined}
            ref={(element) => { tabRefs.current[index] = element; }}
            type="button"
            role="tab"
            aria-selected={isActive}
            tabIndex={isActive ? 0 : -1}
            onClick={() => onChange?.(id)}
            onKeyDown={(event) => handleKeyDown(event, index)}
            className={clsx(TAB, isActive ? TAB_ACTIVE : TAB_INACTIVE)}
          >
            {Icon && <Icon size={18} aria-hidden="true" className="shrink-0" />}
            {itemLabel}
          </button>
        );
      })}
    </div>
  );
};

export default Tabs;
