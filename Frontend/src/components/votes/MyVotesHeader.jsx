import React, { useMemo } from 'react';
import { Filter } from 'lucide-react';

// Componentes
import SectionHeader from '../ui/SectionHeader.jsx';
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

  return (
    <SectionHeader
      title="Historial de "
      highlight="Votaciones"
      subtitle="Revisa cómo has valorado los diferentes ítems."
    >
      <div className="flex gap-2 mr-auto mb-2 md:mb-0 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 scrollbar-hide">
        <Button
          onClick={() => setView('all')}
          variant="solid"
          color={currentView === 'all' ? 'primary' : 'neutral'}
          className={`px-4 py-2 text-sm font-bold whitespace-nowrap ${currentView === 'all' ? 'shadow-md' : 'bg-slate-100 text-nav-item hover:bg-slate-200'}`}
        >
          Todos
        </Button>
        <Button
          onClick={() => setView('vote')}
          variant="solid"
          color={currentView === 'vote' ? 'primary' : 'neutral'}
          className={`px-4 py-2 text-sm font-bold whitespace-nowrap ${currentView === 'vote' ? 'shadow-md' : 'bg-slate-100 text-nav-item hover:bg-slate-200'}`}
        >
          Votados
        </Button>
        <Button
          onClick={() => setView('skip')}
          variant="solid"
          color={currentView === 'skip' ? 'primary' : 'neutral'}
          className={`px-4 py-2 text-sm font-bold whitespace-nowrap ${currentView === 'skip' ? 'shadow-md' : 'bg-slate-100 text-nav-item hover:bg-slate-200'}`}
        >
          Pasados
        </Button>
      </div>

      <div className="flex flex-1 md:flex-none justify-end gap-3 items-center">
        <SelectFilter
          icon={Filter}
          value={currentCategory || ''}
          onChange={handleCategoryChange}
          options={categoryOptions}
          defaultOption="Todas las Categorías"
        />

        <div className="flex flex-col items-center justify-center p-2 bg-background rounded-xl min-w-[70px] ml-2">
          <span className="text-xl text-text-highlight font-black">{meta.total || 0}</span>
          <span className="text-[10px] uppercase text-slate-400 font-bold">Total</span>
        </div>
      </div>
    </SectionHeader>
  );
};

export default MyVotesHeader;