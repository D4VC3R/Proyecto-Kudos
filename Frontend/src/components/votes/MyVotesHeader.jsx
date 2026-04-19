import React from 'react';
import { useCategories } from '../../hooks/categories/useCategoryQueries';
import { SectionHeader } from '../common/SectionHeader';

export const MyVotesHeader = ({ meta, currentView, currentCategory, updateParams }) => {
  const { data: categories } = useCategories();

  const setView = (type) => {
    updateParams({ type: type === 'all' ? null : type, page: 1 });
  };

  const handleCategoryChange = (e) => {
    const value = e.target.value;
    updateParams({ category_slug: value || null, page: 1 });
  };

  return (
    <SectionHeader
      title="Historial de"
      highlight="Votos"
      highlightColor="blue-600"
      subtitle="Revisa cómo has valorado los diferentes ítems."
    >
      <div className="flex gap-2 mr-auto mb-2 md:mb-0 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 scrollbar-hide">
        <button
          onClick={() => setView('all')}
          className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors whitespace-nowrap ${
            currentView === 'all'
              ? 'bg-blue-600 text-white shadow-md'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          Todos
        </button>
        <button
          onClick={() => setView('vote')}
          className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors whitespace-nowrap ${
            currentView === 'vote'
              ? 'bg-blue-600 text-white shadow-md'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          Votados
        </button>
        <button
          onClick={() => setView('skip')}
          className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors whitespace-nowrap ${
            currentView === 'skip'
              ? 'bg-blue-600 text-white shadow-md'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          Pasados
        </button>
      </div>

      <div className="flex flex-1 md:flex-none justify-end gap-3 items-center">
        <select
          value={currentCategory || ''}
          onChange={handleCategoryChange}
          className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
          <option value="">Todas las Categorías</option>
          {Array.isArray(categories) && categories.map((cat) => (
            <option key={cat.id} value={cat.slug}>
              {cat.name}
            </option>
          ))}
        </select>

        <div className="flex flex-col items-center justify-center p-2 bg-slate-50 rounded-xl min-w-[70px] ml-2">
          <span className="text-xl text-slate-900 font-black">{meta.total || 0}</span>
          <span className="text-[10px] uppercase text-slate-400 font-bold">Total</span>
        </div>
      </div>
    </SectionHeader>
  );
};
