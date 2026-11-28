import React, { useMemo } from 'react';
import { Filter } from 'lucide-react';

// Componentes
import SectionHead from '../ui/SectionHead.jsx';
import Button from '../ui/Button.jsx';
import SelectFilter from '../ui/SelectFilter.jsx';

// Hooks
import { useCategories } from '../../hooks/categories/useCategoryQueries.js';

const MyVotesHeader = ({ meta, currentView, currentCategory, updateParams }) => {
  const { data: categories } = useCategories();

  const setView = (type) => {
    updateParams({ type: type === 'all' ? null : type, page: 1 });
  };

  const handleCategoryChange = (e) => {
    const value = e.target.value;
    updateParams({ category_slug: value || null, page: 1 });
  };

  const categoryOptions = useMemo(() => {
    if (!Array.isArray(categories)) return [];
    return categories.map((cat) => ({
      value: cat.slug,
      label: cat.name
    }));
  }, [categories]);

  const views = [
    { id: 'all', label: 'Todos' },
    { id: 'vote', label: 'Votados' },
    { id: 'not_interested', label: 'No me interesan' },
  ];

  return (
    <div className="flex flex-col gap-4">
      <SectionHead title="Historial de Votaciones" />
      <p className="text-small text-ink-muted">Echa un vistazo a tu historial de votaciones.</p>

      <div className="flex flex-wrap items-center gap-3">
        <div role="group" aria-label="Tipo de voto" className="flex flex-wrap gap-2">
          {views.map(({ id, label }) => (
            <Button
              key={id}
              variant="secondary"
              aria-pressed={currentView === id}
              onClick={() => setView(id)}
            >
              {label}
            </Button>
          ))}
        </div>

        <SelectFilter
          label="Categoría"
          icon={Filter}
          value={currentCategory || ''}
          onChange={handleCategoryChange}
          options={categoryOptions}
          defaultOption="Todas las Categorías"
        />

        <p data-votes-total className="text-small text-ink-muted">{meta.total || 0} en total</p>
      </div>
    </div>
  );
};

export default MyVotesHeader;
