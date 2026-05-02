import React from 'react';
import clsx from 'clsx';

export const ProfileTabs = ({ tabs, activeTab, setActiveTab }) => {
  return (
    <div className="w-full flex overflow-x-auto border-b border-slate-200 scrollbar-hide mb-6 gap-2 md:gap-8 min-w-0">
      {tabs.map((tab) => (
        <button
          key={tab.id}
          onClick={() => setActiveTab(tab.id)}
          className={clsx(
            "flex whitespace-nowrap items-center gap-2 py-4 px-1 md:px-2 border-b-2 font-bold transition-all text-sm md:text-base",
            activeTab === tab.id
              ? "border-blue-600 text-blue-600"
              : "border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300"
          )}
        >
          <tab.icon size={18} />
          <span>{tab.name}</span>
        </button>
      ))}
    </div>
  );
};