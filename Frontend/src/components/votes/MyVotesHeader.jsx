import React from 'react';
import { ThumbsUp } from 'lucide-react';

export const MyVotesHeader = ({ meta, currentView, updateParams }) => {
  const setView = (type) => {
    updateParams({ type: type === 'all' ? null : type, page: 1 });
  };

  return (
    <div className="flex flex-col md:flex-row md:items-end justify-between border-b border-slate-200 pb-4">
      <div>
        <h2 className="text-2xl font-black tracking-tight text-slate-900">
          Historial de <span className="text-blue-600 drop-shadow-sm">Votos</span>
        </h2>
        <p className="mt-1 text-sm text-slate-500 font-medium">
          Revisa cmo has valorado los diferentes ítems.
        </p>
      </div>

      <div className="mt-4 md:mt-0 flex gap-2 sm:gap-4 flex-wrap">
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
      </div>
    </div>
  );
};
