import React from 'react';
import { Button } from '../common/Button';

export const ProfileTabs = ({ tabs, activeTab, setActiveTab }) => {
  return (
    <div className="w-full flex overflow-x-auto border-b border-slate-200 scrollbar-hide mb-6 gap-2 md:gap-8 min-w-0">
      {tabs.map((tab) => (
        <Button
          key={tab.id}
          onClick={() => setActiveTab(tab.id)}
          variant="tab"
          color={activeTab === tab.id ? 'active' : 'inactive'}
          className="whitespace-nowrap md:px-2 py-4 ml-2"
          icon={tab.icon}
        >
          {tab.name}
        </Button>
      ))}
    </div>
  );
};