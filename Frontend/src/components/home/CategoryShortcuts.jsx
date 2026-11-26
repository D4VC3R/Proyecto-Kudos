import React from 'react';
import { Link } from 'react-router-dom';
import { resolveCategoryIcon } from '../../lib/formatters.js';

// Accesos rápidos a las categorías en la portada (F2-09). Sin categorías no pinta nada.
const CategoryShortcuts = ({ categories = [] }) => {
  if (!categories?.length) return null;

  return (
    <nav aria-label="Categorías">
      <ul className="flex gap-3 overflow-x-auto pb-2">
        {categories.map((category) => {
          const Icon = resolveCategoryIcon(category);
          return (
            <li key={category.id} className="shrink-0">
              <Link
                to={`/${category.slug}`}
                className="flex items-center gap-2 rounded-full bg-surface px-4 py-2 font-bold text-text-highlight shadow-sm ring-1 ring-border transition-colors hover:ring-primary"
              >
                <Icon size={18} />
                {category.name}
              </Link>
            </li>
          );
        })}
      </ul>
    </nav>
  );
};

export default CategoryShortcuts;
