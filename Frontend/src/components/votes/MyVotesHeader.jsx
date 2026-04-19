import React from 'react';
import { ThumbsUp } from 'lucide-react';
import { SectionHeader } from '../common/SectionHeader';

export const MyVotesHeader = ({ meta, currentView, updateParams }) => {
  const setView = (type) => {
    updateParams({ type: type === 'all' ? null : type, page: 1 });
  };

  return (
    <SectionHeader
      title="Historial de"
      highlight="Votos"
      highlightColor="blue-600"
      subtitle="Revisa cómo has valorado los diferentes ítems."
    >
      <button
        onClick={() => setView('all')}
        className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors ${
          currentView === 'all'
            ? 'bg-blue-600 text-white shadow-md'
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
        }`}
      >
        Todos
      </button>
      <button
        onClick={() => setView('vote')}
        className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors ${
          currentView === 'vote'
            ? 'bg-blue-600 text-white shadow-md'
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
        }`}
      >
        Votados
      </button>
      <button
        onClick={() => setView('skip')}
        className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors ${
          currentView === 'skip'
            ? 'bg-blue-600 text-white shadow-md'
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
        }`}
      >
        Pasados
      </button>

      <div className="flex flex-col items-center justify-center p-2 bg-slate-50 rounded-xl min-w-[70px] ml-2">
        <span className="text-xl text-slate-900 font-black">{meta.total || 0}</span>
        <span className="text-[10px] uppercase text-slate-400 font-bold">Total</span>
      </div>
    </SectionHeader>
  );
};
