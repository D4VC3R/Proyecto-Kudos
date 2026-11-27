import React from 'react';
import { Link } from 'react-router-dom';
import Card from '../ui/Card.jsx';
import { resolveCategoryIcon } from '../../lib/formatters.js';

// Accesos rápidos a las categorías en la portada: 2 por fila en móvil y 4 desde tableta. Sin categorías no pinta nada.
const CategoryShortcuts = ({ categories = [] }) => {
  if (!categories?.length) return null;

  return (
    <nav aria-label="Categorías">
      <ul className="grid grid-cols-2 gap-2 md:grid-cols-4">
        {categories.map((category) => {
          const Icon = resolveCategoryIcon(category);
          return (
            <li key={category.id}>
              <Card as={Link} to={`/${category.slug}`} variant="interactive" className="flex items-center gap-2.5 px-4 py-3.5 font-semibold">
                <Icon size={22} className="text-cat-icon" aria-hidden="true" />
                {category.name}
              </Card>
            </li>
          );
        })}
      </ul>
    </nav>
  );
};

export default CategoryShortcuts;
